<?php

namespace Filament\Http\Livewire\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\HasForm;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class RequestPassword extends Component
{
    use HasForm;
    use WithRateLimiting;

    public $email;

    public function getForm()
    {
        return Form::make()
            ->schema([
                Components\TextInput::make('email')
                    ->label('filament::fields.labels.email')
                    ->hint('[' . __('filament::auth.backToLogin') . '](' . route('filament.auth.login') . ')')
                    ->email()
                    ->autofocus()
                    ->autocomplete('email')
                    ->required()
                    ->email(),
            ])
            ->context(static::class);
    }

    public function submit()
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->addError('email', __('auth.throttle', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]));

            return;
        }

        $requestStatus = Password::broker('filament_users')->sendResetLink($this->validate());

        if (Password::RESET_LINK_SENT !== $requestStatus) {
            $this->addError('email', __('filament::auth.' . $requestStatus));

            return;
        }

        $this->dispatchBrowserEvent('notify', __('filament::auth.' . $requestStatus));
    }

    public function render()
    {
        return view('filament::.auth.request-password', [
            'title' => 'Request Password',
        ])
            ->layout('filament::components.layouts.auth', ['title' => 'filament::auth.resetPassword']);
    }
}
