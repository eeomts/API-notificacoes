<?php
/**
 * Exemplos de uso do NotificationService no Laravel
 */

use App\Services\NotificationService;

// Via injeção de dependência no controller
class OrderController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function store(Request $request)
    {
        $order = Order::create($request->validated());

        // Notifica o entregador assim que o pedido é criado
        $this->notifications->sendToUser(
            userId: (string) $order->deliverer_id,
            title:  'Novo pedido!',
            body:   "Pedido #{$order->id} aguarda retirada.",
            data:   ['order_id' => (string) $order->id, 'action' => 'open_order']
        );

        return response()->json($order, 201);
    }

    public function delivered(Order $order)
    {
        $order->markAsDelivered();

        // Notifica o cliente quando o pedido é entregue
        $this->notifications->sendToUser(
            userId: (string) $order->customer_id,
            title:  'Entrega confirmada!',
            body:   "Seu pedido #{$order->id} foi entregue.",
            data:   ['order_id' => (string) $order->id]
        );

        return response()->json(['message' => 'Entrega confirmada.']);
    }
}

// Via facade/resolve manual
$notifications = app(NotificationService::class);

// Registrar token quando o usuário faz login no app mobile
$notifications->registerToken(
    fcmToken: $request->input('fcm_token'),
    platform: $request->input('platform'),   // android | ios | web
    userId:   (string) auth()->id(),
    extra:    ['app_version' => $request->input('app_version')]
);

// Remover token quando o usuário faz logout
$notifications->removeToken($request->input('fcm_token'));

// Broadcast de alerta para todos
$notifications->broadcast(
    title:    'Manutenção programada',
    body:     'O sistema ficará indisponível às 02h.',
    platform: 'android' // opcional
);
