<?php

namespace SocialiteProviders\Wonde;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'WONDE';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(\env('WONDE_SSO_URL', 'https://edu.wonde.com/') . 'oauth/authorize', $state) . '&mode=' . \env('WONDE_SSO_MODE');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return \env('WONDE_API_URL', 'https://api.wonde.com/') . 'oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(\env('WONDE_API_URL', 'https://api.wonde.com/') . 'graphql/me?query=%7B%0A%20%20Me%7B%0A%20%20%20%20id%0A%20%20%20%20first_name%0A%20%20%20%20last_name%0A%20%20%20%20email%0A%20%20%20%20mobile%0A%20%20%20%20School%20%7B%0A%20%20%20%20%20%20id%0A%20%20%20%20%20%20name%0A%20%20%20%20%20%20establishment_number%0A%20%20%20%20%20%20la_code%0A%20%20%20%20%20%20urn%0A%20%20%20%20%7D%0A%20%20%20%20Person%7B%0A%20%20%20%20%20%20__typename%0A%20%20%20%20%20%20...on%20Employee%7B%0A%20%20%20%20%20%20%20%20forename%0A%09%09%09%09surname%0A%20%20%20%20%20%20%7D%0A%20%20%20%20%7D%0A%20%20%7D%0A%7D', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array $user
     */
    private function getName(array $user)
    {
        return $user['data']['Me']['first_name'] && $user['data']['Me']['last_name'] ? $user['data']['Me']['first_name'].' '.$user['data']['Me']['last_name'] : null;
    }

    /**
     * Get the users email address.
     *
     * @param array $user
     *
     * @return string|null
     */
    private function getEmail(array $user)
    {
        if (!empty($user['data']['Me']['email'])) {
            return $user['data']['Me']['email'];
        } else {
            return null;
        }
    }

    /**
     * Get the users mobile.
     *
     * @param array $user
     *
     * @return string|null
     */
    private function getMobile(array $user)
    {
        if (!empty($user['data']['Me']['mobile'])) {
            return $user['data']['Me']['mobile'];
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['data']['Me']['id'] ?? null,
            'nickname' => null,
            'name' => $this->getName($user),
            'email' => $this->getEmail($user),
            'mobile' => $this->getMobile($user),
            'avatar' => null,
            'school' => $user['data']['Me']['School'] ?? [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
