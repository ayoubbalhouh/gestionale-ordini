<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_allowed_transitions(): void
    {
        $this->assertTrue(OrderStatus::Bozza->canMoveTo(OrderStatus::Confermato));
        $this->assertTrue(OrderStatus::Confermato->canMoveTo(OrderStatus::Spedito));
        $this->assertTrue(OrderStatus::Spedito->canMoveTo(OrderStatus::Consegnato));
        $this->assertTrue(OrderStatus::Confermato->canMoveTo(OrderStatus::Annullato));
    }

    public function test_forbidden_transitions(): void
    {
        $this->assertFalse(OrderStatus::Bozza->canMoveTo(OrderStatus::Spedito));
        $this->assertFalse(OrderStatus::Spedito->canMoveTo(OrderStatus::Annullato));
        $this->assertSame([], OrderStatus::Consegnato->next());
        $this->assertSame([], OrderStatus::Annullato->next());
    }

    public function test_which_states_hold_stock(): void
    {
        $this->assertFalse(OrderStatus::Bozza->holdsStock());
        $this->assertTrue(OrderStatus::Confermato->holdsStock());
        $this->assertTrue(OrderStatus::Consegnato->holdsStock());
        $this->assertFalse(OrderStatus::Annullato->holdsStock());
    }
}
