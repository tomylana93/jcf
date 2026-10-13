<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

describe('Production password defaults', function () {
    beforeEach(function (): void {
        app()->instance('env', 'production');
    });

    it('rejects passwords that do not meet the strength requirements', function (string $password): void {
        mock(UncompromisedVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->andReturnTrue();
        });

        $validator = Validator::make(['password' => $password], ['password' => Password::defaults()]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    })->with([
        'too short' => 'Short1!',
        'missing uppercase' => 'strong-password1!',
        'missing lowercase' => 'STRONG-PASSWORD1!',
        'missing letters' => '123456789012!',
        'missing numbers' => 'Strong-Password!',
        'missing symbols' => 'StrongPassword123',
    ]);

    it('accepts a strong password absent from data breaches', function (): void {
        mock(UncompromisedVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->once()
                ->with(['value' => 'Strong-Password1!', 'threshold' => 0])
                ->andReturnTrue();
        });

        $validator = Validator::make(['password' => 'Strong-Password1!'], ['password' => Password::defaults()]);

        expect($validator->passes())->toBeTrue();
    });

    it('rejects a strong password found in a data breach', function (): void {
        mock(UncompromisedVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->once()
                ->with(['value' => 'Strong-Password1!', 'threshold' => 0])
                ->andReturnFalse();
        });

        $validator = Validator::make(['password' => 'Strong-Password1!'], ['password' => Password::defaults()]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });
});
