<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UsernameTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_username_generated_in_correct_format()
    {
        $this->assertEquals('jdoe', User::generateUsername('John', 'Doe'));
        $this->assertEquals('meggerbachler', User::generateUsername('Maria', 'Egger-Bachler'));
        $this->assertEquals('eosterreicher', User::generateUsername('Edwald', 'Österreicher'));
        $this->assertEquals('hmullerwullrichschmidt', User::generateUsername('Hans-Peter', 'Müller-Wüllrich Schmidt'));
    }
}
