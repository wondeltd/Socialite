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
        return $this->buildAuthUrlFromBase(\env('WONDE_SSO_URL', 'https://edu.wonde.com/').'oauth/authorize', $state).'&mode='.\env('WONDE_SSO_MODE');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return \env('WONDE_API_URL', 'https://api.wonde.com/').'oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $query = $this->getUserQuery();
        $response = $this->getHttpClient()->get(\env('WONDE_API_URL', 'https://api.wonde.com/').'graphql/me?query=' . $query, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array $user
     */
    protected function getName(array $user)
    {
        return $user['data']['Me']['Person']['forename'] . ' ' . $user['data']['Me']['Person']['surname'];
    }

    /**
     * Get the users email address.
     *
     * @param array $user
     *
     * @return string|null
     */
    protected function getEmail(array $user)
    {
        if (!empty($user['data']['Me']['Person']['ContactDetails']['email'])) {
            return $user['data']['Me']['Person']['ContactDetails']['email'];
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
    protected function getMobile(array $user)
    {
        if (!empty($user['data']['Me']['Person']['ContactDetails']['telephone_mobile'])) {
            return $user['data']['Me']['Person']['ContactDetails']['telephone_mobile'];
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
            'id' => $user['data']['Me']['Person']['id'] ?? null,
            'nickname' => null,
            'name' => $this->getName($user),
            'email' => $this->getEmail($user),
            'mobile' => $this->getMobile($user),
            'avatar' => null,
            'school' => $user['data']['Me']['Person']['School'] ?? [],
        ]);
    }

    /**
     * Returns the url encoded qraphql query for the current user
     * @return string
     */
    protected function getUserQuery()
    {
        $query = <<<'GRAPHQL'
            {
              Me{
                Person{
                  __typename
                  ...on Employee {
                    id
                    title
                    surname
                    forename
                    School {
                      id
                      name
                      establishment_number
                      la_code
                      urn
                      address_line_1
                      address_line_2
                      address_town
                      address_postcode
                      country
                    }
                    ContactDetails {
                      email
                      telephone_mobile
                    }
                  },
                  ...on Student {
                    id
                    surname
                    forename
                    School {
                      id
                      name
                      establishment_number
                      la_code
                      urn
                      address_line_1
                      address_line_2
                      address_town
                      address_postcode
                      country
                    }
                  }
                  ...on Contact {
                    id
                    surname
                    forename
                  }
                }
              }
            }
        GRAPHQL;

        return urlencode($query);
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
