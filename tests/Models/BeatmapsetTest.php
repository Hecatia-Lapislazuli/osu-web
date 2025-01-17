<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace Tests\Models;

use App\Enums\Ruleset;
use App\Exceptions\AuthorizationException;
use App\Jobs\CheckBeatmapsetCovers;
use App\Jobs\Notifications\BeatmapsetDisqualify;
use App\Jobs\Notifications\BeatmapsetResetNominations;
use App\Models\Beatmap;
use App\Models\BeatmapDiscussion;
use App\Models\Beatmapset;
use App\Models\BeatmapsetNomination;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Bus;
use Database\Factories\BeatmapsetFactory;
use Queue;
use Tests\TestCase;

class BeatmapsetTest extends TestCase
{
    public function testLove()
    {
        $user = User::factory()->create();
        $beatmapset = $this->beatmapsetFactory()->create();

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $beatmapset->love($user);

        $this->assertSame($notifications + 1, Notification::count());
        $this->assertSame($userNotifications + 1, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isLoved());
        $this->assertSame('loved', $beatmapset->beatmaps()->first()->status());

        Bus::assertDispatched(CheckBeatmapsetCovers::class);
    }

    public function testLoveBeatmapApprovedStates(): void
    {
        $user = User::factory()->create();
        $beatmapset = $this->beatmapsetFactory()->create();

        $specifiedBeatmap = $beatmapset->beatmaps()->first();
        $beatmapset->beatmaps()->saveMany([
            $graveyardBeatmap = Beatmap::factory()->make(['approved' => Beatmapset::STATES['graveyard']]),
            $pendingBeatmap = Beatmap::factory()->make(['approved' => Beatmapset::STATES['pending']]),
            $wipBeatmap = Beatmap::factory()->make(['approved' => Beatmapset::STATES['wip']]),
            $rankedBeatmap = Beatmap::factory()->make(['approved' => Beatmapset::STATES['ranked']]),
        ]);

        $beatmapset->love($user, [$specifiedBeatmap->getKey()]);

        $this->assertTrue($beatmapset->fresh()->isLoved());
        $this->assertSame('loved', $specifiedBeatmap->fresh()->status());
        $this->assertSame('graveyard', $graveyardBeatmap->fresh()->status());
        $this->assertSame('graveyard', $pendingBeatmap->fresh()->status());
        $this->assertSame('graveyard', $wipBeatmap->fresh()->status());
        $this->assertSame('ranked', $rankedBeatmap->fresh()->status());

        Bus::assertDispatched(CheckBeatmapsetCovers::class);
    }

    // region single-playmode beatmap sets
    public function testNominate()
    {
        $beatmapset = $this->beatmapsetFactory()->create();
        $user = User::factory()->withGroup('bng', $beatmapset->playmodesStr())->create();

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $result = $beatmapset->nominate($user, [$beatmapset->playmodesStr()[0]]);

        $this->assertTrue($result['result']);
        $this->assertSame($notifications + 1, Notification::count());
        $this->assertSame($userNotifications + 1, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isPending());
    }

    public function testNominateNATAnyRuleset(): void
    {
        $beatmapset = $this->beatmapsetFactory()->create();
        $user = User::factory()->withGroup('nat', [])->create();

        $this->expectCountChange(fn () => $beatmapset->nominations, 1);
        $this->expectCountChange(fn () => $beatmapset->beatmapsetNominations()->current()->count(), 1);

        $beatmapset->nominate($user, $beatmapset->playmodesStr());
        $beatmapset->refresh();
    }

    public function testQualify()
    {
        $beatmapset = $this->beatmapsetFactory()->create();
        $user = User::factory()->withGroup('bng', $beatmapset->playmodesStr())->create();

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $beatmapset->qualify($user);

        $this->assertSame($notifications + 1, Notification::count());
        $this->assertSame($userNotifications + 1, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isQualified());

        Bus::assertDispatched(CheckBeatmapsetCovers::class);
    }

    public function testLimitedBNGQualifyingNominationBNGNominated()
    {
        $beatmapset = $this->beatmapsetFactory()->create();
        $this->fillNominationsExceptLastForMode($beatmapset, 'bng', $beatmapset->playmodesStr()[0]);

        $nominator = User::factory()->withGroup('bng_limited', $beatmapset->playmodesStr())->create();

        priv_check_user($nominator, 'BeatmapsetNominate', $beatmapset)->ensureCan();

        $result = $beatmapset->nominate($nominator, [$beatmapset->playmodesStr()[0]]);

        $this->assertTrue($result['result']);
        $this->assertTrue($beatmapset->fresh()->isQualified());

        Bus::assertDispatched(CheckBeatmapsetCovers::class);
    }

    public function testLimitedBNGQualifyingNominationNATNominated()
    {
        $beatmapset = $this->beatmapsetFactory()->create();
        $this->fillNominationsExceptLastForMode($beatmapset, 'nat', $beatmapset->playmodesStr()[0]);

        $nominator = User::factory()->withGroup('bng_limited', $beatmapset->playmodesStr())->create();

        priv_check_user($nominator, 'BeatmapsetNominate', $beatmapset)->ensureCan();

        $result = $beatmapset->nominate($nominator, [$beatmapset->playmodesStr()[0]]);

        $this->assertTrue($result['result']);
        $this->assertTrue($beatmapset->fresh()->isQualified());

        Bus::assertDispatched(CheckBeatmapsetCovers::class);
    }

    public function testLimitedBNGQualifyingNominationLimitedBNGNominated()
    {
        $beatmapset = $this->beatmapsetFactory()->create();
        $this->fillNominationsExceptLastForMode($beatmapset, 'bng_limited', $beatmapset->playmodesStr()[0]);

        $nominator = User::factory()->withGroup('bng_limited', $beatmapset->playmodesStr())->create();

        $this->assertFalse($beatmapset->isQualified());
        $beatmapset->nominate($nominator);
        $this->assertFalse($beatmapset->isQualified());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }
    public function testNominateWithDefaultMetadata()
    {
        $beatmapset = $this->beatmapsetFactory()->state([
            'genre_id' => Genre::UNSPECIFIED,
            'language_id' => Language::UNSPECIFIED,
        ])->create();
        $nominator = User::factory()->withGroup('bng', $beatmapset->playmodesStr())->create();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage(osu_trans('authorization.beatmap_discussion.nominate.set_metadata'));
        priv_check_user($nominator, 'BeatmapsetNominate', $beatmapset)->ensureCan();
    }

    /**
     * @dataProvider dataProviderForTestRank
     */
    public function testRank(string $state, bool $success): void
    {
        $beatmapset = $this->beatmapsetFactory()->$state()->create();

        $otherUser = User::factory()->create();

        $beatmap = $beatmapset->beatmaps()->first();
        $beatmap->scoresBest()->create([
            'user_id' => $otherUser->getKey(),
        ]);

        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $this->expectCountChange(fn () => $beatmapset->bssProcessQueues()->count(), $success ? 1 : 0);
        $this->expectCountChange(fn () => UserNotification::count(), $success ? 1 : 0);
        $this->expectCountChange(fn () => Notification::count(), $success ? 1 : 0);
        $this->expectCountChange(fn () => $beatmap->scoresBest()->count(), $success ? -1 : 0);

        $res = $beatmapset->rank();

        $this->assertSame($success, $res);
        $this->assertSame($success, $beatmapset->fresh()->isRanked());

        if ($success) {
            Bus::assertDispatched(CheckBeatmapsetCovers::class);
        } else {
            Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
        }
    }

    /**
     * @dataProvider rankWithOpenIssueDataProvider
     */
    public function testRankWithOpenIssue(string $type): void
    {
        $beatmapset = $this->beatmapsetFactory()
            ->qualified()
            ->has(BeatmapDiscussion::factory()->general()->messageType($type))->create();

        $this->assertTrue($beatmapset->isQualified());
        $this->assertFalse($beatmapset->rank());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }

    public function testGlobalScopeActive()
    {
        $beatmapset = Beatmapset::factory()->inactive()->create();
        $id = $beatmapset->getKey();

        $this->assertNull(Beatmapset::find($id)); // global scope
        $this->assertNull(Beatmapset::withoutGlobalScopes()->active()->find($id)); // scope still applies after removing global scope
        $this->assertTrue($beatmapset->is(Beatmapset::withoutGlobalScopes()->find($id))); // no global scopes
    }

    public function testGlobalScopeSoftDelete()
    {
        $beatmapset = Beatmapset::factory()->inactive()->deleted()->create();
        $id = $beatmapset->getKey();

        $this->assertNull(Beatmapset::withTrashed()->find($id));
    }

    // endregion

    // region multi-playmode beatmap sets (aka hybrid)
    public function testHybridLegacyNominate(): void
    {
        $user = User::factory()->withGroup('bng', ['osu'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        // create legacy nomination event to enable legacy nomination mode
        BeatmapsetNomination::factory()->create([
            'beatmapset_id' => $beatmapset,
            'user_id' => User::factory()->withGroup('bng', $beatmapset->playmodesStr()),
        ]);

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $result = $beatmapset->nominate($user);

        $this->assertTrue($result['result']);
        $this->assertSame($notifications + 1, Notification::count());
        $this->assertSame($userNotifications + 1, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isPending());
    }

    public function testHybridLegacyQualify(): void
    {
        $user = User::factory()->withGroup('bng', ['osu'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        // create legacy nomination event to enable legacy nomination mode
        BeatmapsetNomination::factory()->create([
            'beatmapset_id' => $beatmapset,
            'user_id' => User::factory()->withGroup('bng', $beatmapset->playmodesStr()),
        ]);

        // fill with legacy nominations
        $count = $beatmapset->requiredNominationCount() - $beatmapset->currentNominationCount() - 1;
        for ($i = 0; $i < $count; $i++) {
            $beatmapset->nominate(User::factory()->withGroup('bng', ['osu'])->create());
        }

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $result = $beatmapset->nominate($user);

        $this->assertTrue($result['result']);
        $this->assertSame($notifications + 1, Notification::count());
        $this->assertSame($userNotifications + 1, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isQualified());

        Bus::assertDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominateWithNullPlaymode(): void
    {
        $user = User::factory()->create();
        $beatmapset = $this->createHybridBeatmapset();

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $result = $beatmapset->nominate($user);

        $this->assertFalse($result['result']);
        $this->assertSame($result['message'], osu_trans('beatmapsets.nominate.hybrid_requires_modes'));

        $this->assertSame($notifications, Notification::count());
        $this->assertSame($userNotifications, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isPending());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominateWithNoPlaymodePermission(): void
    {
        $user = User::factory()->withGroup('bng', ['osu'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $result = $beatmapset->nominate($user, ['taiko']);

        $this->assertFalse($result['result']);
        $this->assertSame($result['message'], osu_trans('beatmapsets.nominate.incorrect_mode', ['mode' => 'taiko']));

        $this->assertSame($notifications, Notification::count());
        $this->assertSame($userNotifications, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isPending());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominateWithPlaymodePermissionSingleMode(): void
    {
        $user = User::factory()->withGroup('bng', ['osu'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $result = $beatmapset->nominate($user, ['osu']);

        $this->assertTrue($result['result']);
        $this->assertSame($notifications + 1, Notification::count());
        $this->assertSame($userNotifications + 1, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isPending());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominateWithPlaymodePermissionTooMany(): void
    {
        $user = User::factory()->withGroup('bng', ['osu'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        $this->fillNominationsExceptLastForMode($beatmapset, 'bng', 'osu');

        $result = $beatmapset->nominate(User::factory()->withGroup('bng', ['osu'])->create(), ['osu']);
        $this->assertTrue($result['result']);

        $result = $beatmapset->fresh()->nominate($user, ['osu']);

        $this->assertFalse($result['result']);
        $this->assertSame($result['message'], osu_trans('beatmaps.nominations.too_many'));
        $this->assertTrue($beatmapset->fresh()->isPending());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominateWithPlaymodePermissionMultipleModes(): void
    {
        $user = User::factory()->withGroup('bng', ['osu', 'taiko'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        $notifications = Notification::count();
        $userNotifications = UserNotification::count();

        $otherUser = User::factory()->create();
        $beatmapset->watches()->create(['user_id' => $otherUser->getKey()]);

        $result = $beatmapset->nominate($user, ['osu', 'taiko']);

        $this->assertTrue($result['result']);
        $this->assertSame($notifications + 1, Notification::count());
        $this->assertSame($userNotifications + 1, UserNotification::count());
        $this->assertTrue($beatmapset->fresh()->isPending());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominationBNGQualifyingBNGNominatedPartial(): void
    {
        $user = User::factory()->withGroup('bng_limited', ['osu', 'taiko'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        $this->fillNominationsExceptLastForMode($beatmapset, 'bng', 'osu');
        $this->fillNominationsExceptLastForMode($beatmapset, 'bng', 'taiko');

        $result = $beatmapset->nominate($user, ['osu']);

        $this->assertTrue($result['result']);
        $this->assertFalse($beatmapset->fresh()->isQualified());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominationLimitedBNGQualifyingLimitedBNGNominated(): void
    {
        $user = User::factory()->withGroup('bng_limited', ['osu', 'taiko'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        $this->fillNominationsExceptLastForMode($beatmapset, 'bng_limited', 'osu');
        $this->fillNominationsExceptLastForMode($beatmapset, 'bng_limited', 'taiko');

        $result = $beatmapset->fresh()->nominate($user, ['osu', 'taiko']);

        $this->assertFalse($result['result']);
        $this->assertSame($result['message'], osu_trans('beatmapsets.nominate.full_bn_required'));
        $this->assertTrue($beatmapset->fresh()->isPending());

        Bus::assertNotDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominationLimitedBNGQualifyingBNGNominated(): void
    {
        $user = User::factory()->withGroup('bng', ['osu', 'taiko'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        $this->fillNominationsExceptLastForMode($beatmapset, 'bng_limited', 'osu');
        $this->fillNominationsExceptLastForMode($beatmapset, 'bng_limited', 'taiko');

        $result = $beatmapset->nominate($user, ['osu', 'taiko']);

        $this->assertTrue($result['result']);
        $this->assertTrue($beatmapset->fresh()->isQualified());

        Bus::assertDispatched(CheckBeatmapsetCovers::class);
    }

    public function testHybridNominationBNGQualifyingLimitedBNGNominated(): void
    {
        $user = User::factory()->withGroup('bng_limited', ['osu', 'taiko'])->create();
        $beatmapset = $this->createHybridBeatmapset();

        $this->fillNominationsExceptLastForMode($beatmapset, 'bng', 'osu');
        $this->fillNominationsExceptLastForMode($beatmapset, 'bng', 'taiko');

        $result = $beatmapset->nominate($user, ['osu', 'taiko']);

        $this->assertTrue($result['result']);
        $this->assertTrue($beatmapset->fresh()->isQualified());

        Bus::assertDispatched(CheckBeatmapsetCovers::class);
    }

    //end region

    // region disqualification

    /**
     * @dataProvider disqualifyOrResetNominationsDataProvider
     */
    public function testDisqualifyOrResetNominations(string $state, string $pushed)
    {
        $user = User::factory()->withGroup('bng')->create();
        $beatmapset = Beatmapset::factory()->owner()->withDiscussion()->$state()->create();
        $discussion = $beatmapset->beatmapDiscussions()->first(); // contents only needed for logging.

        Queue::fake();

        $beatmapset->disqualifyOrResetNominations($user, $discussion);

        Queue::assertPushed($pushed);
    }

    //end region

    public function disqualifyOrResetNominationsDataProvider()
    {
        return [
            ['pending', BeatmapsetResetNominations::class],
            ['qualified', BeatmapsetDisqualify::class],
        ];
    }

    public function dataProviderForTestRank(): array
    {
        return [
            ['pending', false],
            ['qualified', true],
        ];
    }

    public function rankWithOpenIssueDataProvider()
    {
        return [
            ['problem'],
            ['suggestion'],
        ];
    }

    private function beatmapsetFactory(): BeatmapsetFactory
    {
        return Beatmapset::factory()
            ->owner()
            ->pending()
            ->has(Beatmap::factory()->state(fn (array $attr, Beatmapset $set) => ['user_id' => $set->user_id]));
    }

    private function createHybridBeatmapset($rulesets = [Ruleset::osu, Ruleset::taiko]): Beatmapset
    {
        $beatmapset = Beatmapset::factory()
            ->owner()
            ->pending();

        foreach ($rulesets as $ruleset) {
            $beatmapset = $beatmapset->has(
                Beatmap::factory()->state(fn (array $attr, Beatmapset $set) => [
                    'playmode' => $ruleset->value,
                    'user_id' => $set->user_id,
                ])
            );
        }

        return $beatmapset->create();
    }

    private function fillNominationsExceptLastForMode(Beatmapset $beatmapset, string $group, string $playmode): void
    {
        $count = $beatmapset->requiredNominationCount()[$playmode] - $beatmapset->currentNominationCount()[$playmode] - 1;
        for ($i = 0; $i < $count; $i++) {
            $beatmapset->nominate(User::factory()->withGroup($group, [$playmode])->create(), [$playmode]);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Genre::factory()->create(['genre_id' => Genre::UNSPECIFIED]);
        Language::factory()->create(['language_id' => Language::UNSPECIFIED]);

        Bus::fake([CheckBeatmapsetCovers::class]);
    }
}
