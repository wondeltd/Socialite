<?php
namespace SocialiteProviders\Wonde;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WondeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wonde', __NAMESPACE__ . '\Provider');
    }
}
