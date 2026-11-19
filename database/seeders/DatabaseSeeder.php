<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(OrderService $service): void
    {
        $admin = User::create(['name' => 'Giulia Ferri', 'email' => 'admin@ordinato.test', 'password' => 'password', 'role' => 'admin']);
        $operatore = User::create(['name' => 'Marco Zanon', 'email' => 'operatore@ordinato.test', 'password' => 'password', 'role' => 'operatore']);

        $prodotti = [
            ['CAF-001', 'Caffè in grani Espresso Bar 1 kg', 1650, 22, 120, 30],
            ['CAF-002', 'Caffè decaffeinato in grani 500 g', 980, 22, 40, 15],
            ['ZUC-010', 'Zucchero in bustine (1000 pz)', 1290, 10, 60, 20],
            ['LAT-020', 'Latte UHT intero 1 L (cartone 12)', 1380, 4, 18, 25],
            ['ACQ-030', 'Acqua minerale naturale 0,5 L (24 pz)', 690, 22, 200, 40],
            ['ACQ-031', 'Acqua minerale frizzante 0,5 L (24 pz)', 690, 22, 150, 40],
            ['BIB-040', 'Succo di frutta ACE 200 ml (24 pz)', 1490, 22, 35, 10],
            ['VIN-050', 'Prosecco DOC Treviso 0,75 L', 690, 22, 90, 24],
            ['VIN-051', 'Fior d\'Arancio Colli Euganei 0,75 L', 980, 22, 8, 12],
            ['VIN-052', 'Cabernet Colli Berici 0,75 L', 760, 22, 48, 12],
            ['SPR-060', 'Aperitivo per Spritz 1 L', 1290, 22, 64, 18],
            ['MON-070', 'Bicchieri compostabili 200 ml (100 pz)', 590, 22, 300, 50],
            ['MON-071', 'Tovaglioli bar 17x17 (1000 pz)', 790, 22, 26, 30],
            ['MON-072', 'Cannucce in carta (250 pz)', 450, 22, 140, 30],
            ['DOL-080', 'Brioche vuota surgelata (50 pz)', 1890, 10, 22, 15],
            ['DOL-081', 'Tramezzini bianchi (busta 25 fogli)', 690, 4, 4, 10],
            ['PUL-090', 'Detergente lavastoviglie 5 L', 1650, 22, 30, 8],
            ['PUL-091', 'Brillantante 5 L', 1450, 22, 12, 8],
        ];
        $products = collect($prodotti)->map(fn ($p) => Product::create([
            'sku' => $p[0], 'nome' => $p[1], 'prezzo_cents' => $p[2], 'iva' => $p[3], 'giacenza' => 0, 'scorta_minima' => $p[5],
        ]));

        $clienti = [
            ['Bar Pedrocchi Café S.r.l.', 'Padova', 'Via VIII Febbraio 15'],
            ['Osteria dai Do Mori', 'Venezia', 'Calle dei Do Mori 429'],
            ['Pasticceria Riva S.n.c.', 'Treviso', 'Piazza dei Signori 3'],
            ['Caffè Garibaldi', 'Vicenza', 'Corso Palladio 12'],
            ['Ristorante Al Ponte', 'Bassano del Grappa', 'Via Ponte Vecchio 2'],
            ['Pizzeria Brace', 'Padova', 'Via dei Forni 8'],
            ['Agriturismo Casale dei Colli', 'Torreglia', 'Via dei Vigneti 12'],
            ['Hotel Terme Euganee', 'Abano Terme', 'Viale delle Terme 44'],
            ['Bar Sport Castelfranco', 'Castelfranco Veneto', 'Borgo Treviso 7'],
            ['Enoteca Il Calice', 'Verona', 'Via Mazzini 31'],
        ];
        $customers = collect($clienti)->map(fn ($c, $i) => Customer::create([
            'ragione_sociale' => $c[0], 'citta' => $c[1], 'indirizzo' => $c[2],
            'piva' => $this->piva(1000000000 + $i * 7919),
            'email' => 'ordini@'.str($c[0])->slug().'.example',
            'telefono' => '04'.rand(1, 9).' '.rand(100000, 999999),
        ]));

        $oggi = CarbonImmutable::now();

        // Carico iniziale di magazzino, sei mesi fa
        Carbon::setTestNow($oggi->subMonths(6)->startOfMonth());
        foreach ($products as $k => $p) {
            $service->loadStock($p, $prodotti[$k][4] + 300, $admin, 'Inventario iniziale');
        }

        // Ordini degli ultimi 6 mesi
        mt_srand(42);
        for ($m = 5; $m >= 0; $m--) {
            $n = $m === 0 ? 9 : 5 + mt_rand(0, 5);
            for ($k = 0; $k < $n; $k++) {
                $day = $oggi->startOfMonth()->subMonths($m)->addDays(mt_rand(0, 26))->setTime(mt_rand(8, 17), mt_rand(0, 59));
                if ($day->gt($oggi->subDays(2))) {
                    $day = $oggi->subDays(mt_rand(2, 6))->setTime(mt_rand(8, 12), 0);
                }
                Carbon::setTestNow($day);
                $items = $products->random(mt_rand(2, 5))->map(fn ($p) => ['product_id' => $p->id, 'quantita' => mt_rand(1, 12)])->values()->all();
                $order = $service->save([
                    'customer_id' => $customers->random()->id,
                    'data_consegna' => $day->addDays(mt_rand(2, 6))->toDateString(),
                    'items' => $items,
                ], mt_rand(0, 1) ? $admin : $operatore);

                $finale = match (true) {
                    $m >= 2 => mt_rand(0, 9) === 0 ? OrderStatus::Annullato : OrderStatus::Consegnato,
                    $m === 1 => [OrderStatus::Consegnato, OrderStatus::Spedito, OrderStatus::Consegnato][mt_rand(0, 2)],
                    default => [OrderStatus::Bozza, OrderStatus::Confermato, OrderStatus::Confermato, OrderStatus::Spedito][mt_rand(0, 3)],
                };
                $percorso = match ($finale) {
                    OrderStatus::Bozza => [],
                    OrderStatus::Annullato => [OrderStatus::Confermato, OrderStatus::Annullato],
                    OrderStatus::Confermato => [OrderStatus::Confermato],
                    OrderStatus::Spedito => [OrderStatus::Confermato, OrderStatus::Spedito],
                    OrderStatus::Consegnato => [OrderStatus::Confermato, OrderStatus::Spedito, OrderStatus::Consegnato],
                };
                foreach ($percorso as $h => $stato) {
                    Carbon::setTestNow($day->addHours(2 + $h * 30));
                    $service->changeStatus($order, $stato, $admin);
                }
            }
        }
        Carbon::setTestNow();

        // Inventario finale: alcune giacenze basse, così la dashboard mostra i prodotti sotto scorta
        foreach ($products as $k => $p) {
            $diff = $prodotti[$k][4] - $p->refresh()->giacenza;
            if ($diff !== 0) {
                $service->loadStock($p, $diff, $admin, 'Inventario di fine mese');
            }
        }
    }

    /** Genera una partita IVA valida (con cifra di controllo) partendo da 10 cifre */
    private function piva(int $base): string
    {
        $digits = str_pad((string) $base, 10, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $n = (int) $digits[$i];
            if ($i % 2 === 1) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
        }

        return $digits.((10 - $sum % 10) % 10);
    }
}
