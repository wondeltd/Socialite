<?php
namespace SocialiteProviders\Wonde;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use function foo\func;

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
        return $this->buildAuthUrlFromBase('https://edu.wonde.dev/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.wonde.dev/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.wonde.dev/graphql/me?query=%7B%0A%20%20Me%7B%0A%20%20%20%20first_name%0A%20%20%20%20last_name%0A%20%20%20%20id%0A%20%20%20%20Person%7B%0A%20%20%20%20%20%20__typename%0A%20%20%20%20%20%20...on%20Student%7B%0A%20%20%20%20%20%20%20%20forename%0A%20%20%20%20%20%20%20%20surname%0A%20%20%20%20%20%20%20%20ContactDetails%20%7B%0A%20%20%20%20%20%20%20%20%20%20email%0A%20%20%20%20%20%20%20%20%20%20email_home%0A%20%20%20%20%20%20%20%20%20%20email_work%0A%20%20%20%20%20%20%20%20%20%20email_primary%0A%20%20%20%20%20%20%20%20%7D%0A%20%20%20%20%20%20%7D%0A%20%20%20%20%20%20...on%20Employee%7B%0A%20%20%20%20%20%20%20%20forename%0A%09%09%09%09surname%0A%20%20%20%20%20%20%20%20ContactDetails%20%7B%0A%20%20%20%20%20%20%20%20%20%20email%0A%20%20%20%20%20%20%20%20%20%20email_home%0A%20%20%20%20%20%20%20%20%20%20email_work%0A%20%20%20%20%20%20%20%20%20%20email_primary%0A%20%20%20%20%20%20%20%20%7D%0A%20%20%20%20%20%20%7D%0A%20%20%20%20%20%20...on%20Contact%7B%0A%20%20%20%20%20%20%20%20forename%0A%20%20%20%20%20%20%20%20surname%0A%20%20%20%20%20%20%20%20ContactDetails%20%7B%0A%20%20%20%20%20%20%20%20%20%20email%0A%20%20%20%20%20%20%20%20%20%20email_home%0A%20%20%20%20%20%20%20%20%20%20email_work%0A%20%20%20%20%20%20%20%20%20%20email_primary%0A%20%20%20%20%20%20%20%20%7D%0A%20%20%20%20%20%20%7D%0A%20%20%20%20%7D%0A%20%20%7D%0A%7D', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array $user
     * @return void
     */
    private function getName(array $user)
    {
        return $user['data']['Me']['first_name'] && $user['data']['Me']['last_name'] ? $user['data']['Me']['first_name'] . ' ' . $user['data']['Me']['last_name'] : null;
    }

    /**
     * Get the users email address
     * the location of the email address depends on the type of user
     *
     * @param array $user
     * @return string|null
     */
    private function getEmail(array $user)
    {
        if (!empty($user['data']['Me']['Person']['ContactDetails']['email'])) {
            return $user['data']['Me']['Person']['ContactDetails']['email'];
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
            'avatar' => null,
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
