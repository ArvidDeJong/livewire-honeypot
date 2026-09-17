<?php

namespace Darvis\LivewireHoneypot\View\Components;

use Darvis\LivewireHoneypot\Services\HoneypotService;
use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * <x-honeypot /> for Livewire forms and plain forms.
 */
class Honeypot extends Component
{
    /**
     * Screen-reader-only CSS: hidden, but not in a way that is easy to recognise as a honeypot.
     */
    public const HIDDEN_CSS = 'position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip-path:inset(50%)!important;white-space:nowrap!important;border:0!important;';

    public function __construct(
        public ?string $nonce = null,
        public ?string $errorKey = null,
    ) {}

    /**
     * Laravel calls this before the attributes are set, so the wire:model binding is read in the view.
     */
    public function render(): View
    {
        $honeypot = app(HoneypotService::class);
        $livewire = app('view')->shared('__livewire');

        /*
         * Livewire mode only when the component being rendered uses the trait. A plain form inside a
         * Livewire component, such as a newsletter form posting to a controller, needs a signed token.
         */
        $inLivewire = is_object($livewire) && in_array(HasHoneypot::class, class_uses_recursive($livewire), true);
        $token = $inLivewire ? null : $honeypot->token();

        /** @var view-string $view Registered under the package namespace, which Larastan can't resolve. */
        $view = 'livewire-honeypot::components.honeypot';

        return view($view, [
            'inLivewire' => $inLivewire,
            'token' => $token,
            'baitName' => $honeypot->baitName($token ?? Str::random(16)),
            'errorBagKey' => $this->errorKey ?? ($inLivewire ? 'hp_website' : $honeypot->fieldName()),
            'cspNonce' => $this->nonce ?? Vite::cspNonce(),
            'hiddenClass' => $honeypot->wrapperClass(),
            'hiddenCss' => self::HIDDEN_CSS,
        ]);
    }
}
