<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

return [
    'cart' => [
        'checkout' => 'Pagar',
        'empty_cart' => '',
        'info' => ':count_delimited producto en el carrito ($:subtotal)|:count_delimited productos en el carrito ($:subtotal)',
        'more_goodies' => 'Deseo revisar más productos antes de completar la orden',
        'shipping_fees' => 'gastos de envío',
        'title' => 'Carrito de compras',
        'total' => 'total',

        'errors_no_checkout' => [
            'line_1' => 'Oh, oh, ¡hay problemas con su carrito que están impidiendo el pago!',
            'line_2' => 'Elimina o actualiza los elementos de arriba para continuar.',
        ],

        'empty' => [
            'text' => 'Tu carrito está vacío.',
            'return_link' => [
                '_' => '¡Regresa al :link para encontrar algunos productos!',
                'link_text' => 'listado de la tienda',
            ],
        ],
    ],

    'checkout' => [
        'cart_problems' => '¡Oh oh, hay problemas con tu carrito!',
        'cart_problems_edit' => 'Haz clic aquí para editarlo.',
        'declined' => 'El pago ha sido cancelado.',
        'delayed_shipping' => '¡Ahora mismo estamos sobresaturados de pedidos! Eres bienvenido a solicitar tu orden, pero considera un **retraso adicional de 1-2 semanas** mientras nos ponemos al día con órdenes ya existentes.',
        'hide_from_activity' => 'Ocultar todas las etiquetas osu!supporter en esta orden de mi actividad',
        'old_cart' => 'Tu carrito parecía estar desactualizado y fue reiniciado, por favor intenta de nuevo.',
        'pay' => 'Pagar con PayPal',
        'title_compact' => 'caja',

        'has_pending' => [
            '_' => 'Tienes pedidos incompletos, haz clic :link para verlos.',
            'link_text' => 'aquí',
        ],

        'pending_checkout' => [
            'line_1' => 'Un anterior pago ha sido iniciado pero no fue completado.',
            'line_2' => 'Reanuda tu pago seleccionando un método de pago.',
        ],
    ],

    'discount' => 'ahorra un :percent%',
    'free' => '',

    'invoice' => [
        'contact' => '',
        'date' => '',
        'echeck_delay' => 'Como su pago fue un eCheck, ¡por favor permita hasta 10 días adicionales para que el pago se realice a través de PayPal!',
        'hide_from_activity' => 'las etiquetas osu!supporter en esta orden no se muestran en tus actividades recientes.',
        'sent_via' => '',
        'shipping_to' => '',
        'title' => '',
        'title_compact' => 'factura',

        'status' => [
            'cancelled' => [
                'title' => '',
                'line_1' => [
                    '_' => "",
                    'link_text' => '',
                ],
            ],
            'delivered' => [
                'title' => '',
                'line_1' => [
                    '_' => '',
                    'link_text' => '',
                ],
            ],
            'prepared' => [
                'title' => '',
                'line_1' => '',
                'line_2' => '',
            ],
            'processing' => [
                'title' => '¡Aún no se ha confirmado tu pago!',
                'line_1' => 'Si ya ha pagado, puede que aún estemos esperando la confirmación de su pago. ¡Por favor, actualice esta página en un minuto o dos!',
                'line_2' => [
                    '_' => 'Si ha encontrado un problema durante la compra, :link',
                    'link_text' => 'haz clic aquí para reanudar tu pago',
                ],
            ],
            'shipped' => [
                'title' => '',
                'tracking_details' => '',
                'no_tracking_details' => [
                    '_' => "",
                    'link_text' => '',
                ],
            ],
        ],
    ],

    'order' => [
        'cancel' => 'Cancelar la orden',
        'cancel_confirm' => 'Esta orden será cancelada y no se aceptará el pago por ella. El proveedor de pagos podría no liberar inmediatamente los fondos reservados. ¿Está seguro?',
        'cancel_not_allowed' => 'Esta orden no puede ser cancelada en este momento.',
        'invoice' => 'Ver factura',
        'no_orders' => 'No hay órdenes para ver.',
        'paid_on' => 'Orden realizada :date',
        'resume' => 'Reanudar pago',
        'shipping_and_handling' => '',
        'shopify_expired' => 'El enlace de pago de esta orden ha expirado.',
        'subtotal' => '',
        'total' => '',

        'details' => [
            'order_number' => '',
            'payment_terms' => '',
            'salesperson' => '',
            'shipping_method' => '',
            'shipping_terms' => '',
            'title' => '',
        ],

        'item' => [
            'quantity' => 'Cantidad',

            'display_name' => [
                'supporter_tag' => ':name para :username (:duration)',
            ],

            'subtext' => [
                'supporter_tag' => 'Mensaje: :message',
            ],
        ],

        'not_modifiable_exception' => [
            'cancelled' => 'No puedes modificar tu orden porque ha sido cancelada.',
            'checkout' => 'No puedes modificar tu orden mientras está siendo procesada.', // checkout and processing should have the same message.
            'default' => 'La orden no es modificable',
            'delivered' => 'No puedes modificar tu orden porque ya ha sido entregada.',
            'paid' => 'No puedes modificar tu orden porque ya ha sido pagada.',
            'processing' => 'No puedes modificar tu orden mientras está siendo procesada.',
            'shipped' => 'No puedes modificar tu orden porque ya ha sido enviada.',
        ],

        'status' => [
            'cancelled' => 'Cancelada',
            'checkout' => 'Preparando',
            'delivered' => 'Enviada',
            'paid' => 'Pagada',
            'processing' => 'Confirmación pendiente',
            'shipped' => 'En tránsito',
            'title' => '',
        ],

        'thanks' => [
            'title' => '',
            'line_1' => [
                '_' => '',
                'link_text' => '',
            ],
        ],
    ],

    'product' => [
        'name' => 'Nombre',

        'stock' => [
            'out' => 'Este producto está actualmente agotado. ¡Vuelva más tarde!',
            'out_with_alternative' => 'Lamentablemente, este artículo esta agotado. ¡Usa el menú desplegable para elegir un tipo diferente o vuelve más tarde!',
        ],

        'add_to_cart' => 'Agregar al carrito',
        'notify' => '¡Notificarme cuando esté disponible!',

        'notification_success' => 'serás notificado cuando tengamos más existencias. Haz clic :link para cancelar',
        'notification_remove_text' => 'aquí',

        'notification_in_stock' => '¡Este producto ya tiene existencias!',
    ],

    'supporter_tag' => [
        'gift' => 'regalar al jugador',
        'gift_message' => '¡añade un mensaje opcional a tu regalo! (hasta :length caracteres) ',

        'require_login' => [
            '_' => '¡Tienes que tener una :link para obtener una etiqueta osu!supporter!',
            'link_text' => 'sesión iniciada',
        ],
    ],

    'username_change' => [
        'check' => '¡Escribe un nombre de usuario para revisar su disponibilidad!',
        'checking' => 'Revisando la disponibilidad de :username...',
        'placeholder' => '',
        'label' => '',
        'current' => '',

        'require_login' => [
            '_' => '¡Tienes que tener una :link para cambiar tu nombre de usuario!',
            'link_text' => 'sesión iniciada',
        ],
    ],

    'xsolla' => [
        'distributor' => '',
    ],
];
