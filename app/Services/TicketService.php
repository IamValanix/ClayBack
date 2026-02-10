namespace App\Services;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TicketService
{
public function createPurchase(array $data)
{
return DB::transaction(function () use ($data) {
$order = Order::create([
'customer_name' => strip_tags($data['name']), // Sanitización extra
'customer_email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
'amount' => 100.00,
'status' => 'completed',
'payment_gateway' => 'simulation',
'payment_id' => 'SIM-' . Str::random(12),
]);

$ticket = Ticket::create([
'order_id' => $order->id,
'ticket_code' => 'TKT-' . Str::random(8),
]);

return [$order, $ticket];
});
}
}