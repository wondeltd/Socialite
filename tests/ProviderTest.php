<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Guzzle\Tests\GuzzleTestCase;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\EntityBody;
use SocialiteProviders\Wonde\Provider;
use Laravel\Socialite\Two\User;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ProviderTest extends TestCase
{

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    /**
     * A basic auth test that requires your client_id, client_secret and redirect.
     *
     * Please replace anything with the word FAKE with your real data. for example,
     * 'fake_code' and 'access_token'
     *
     * @return void
     */
    public function test_can_auth_and_run_graphql_query()
    {
        $request = m::mock(Request::class);
        $request->allows('input')->with('code')->andReturns('fake_code');

        $accessTokenResponse = m::mock(ResponseInterface::class);
        $accessTokenResponse->allows('getBody')->andReturns(json_decode(['access_token' => 'fake_access_token']));

        $basicUserResponse = m::mock(ResponseInterface::class);
        $basicUserResponse->allows('getBody')->andReturns(json_encode([['data']['Me']['Person']['forename'] => $forename = 'fake_forname'])); // test person, school or company

        $query  = $this->getUserQuery();

        $guzzle = m::mock(Client::class);
        $guzzle->expects('post')->andReturns($accessTokenResponse);
        $guzzle->allows('get')->with('https://api.wonde.com/graphql/me?query=' . $query, [
            'headers' => [
                'Authorization' => 'Bearer fake-token',
            ]
        ])->andReturns($basicUserResponse);

        $provider = new Provider($request, 'client_id', 'client_secret', 'redirect');
        $provider->stateless();
        $provider->setHttpClient($guzzle);

        $this->assertInstanceOf(User::class, $provider);
        $this->assertSame($forename, $provider->getName());
        $this->assertNull($provider->getEmail());
    }

    public function getUserQuery()
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

}