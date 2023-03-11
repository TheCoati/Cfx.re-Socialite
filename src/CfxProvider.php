<?php

namespace TheCoati\CfxSocialite;

use ArrayAccess;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class CfxProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * CFX.re authentication base url endpoint.
     *
     * @var string
     */
    private static string $BASE_URL = 'https://forum.cfx.re';

    /**
     * Generates a randomized state/ nonce.
     *
     * @throws Exception
     */
    public function getState(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Returns the contents of the stored public key.
     *
     * @return string
     */
    protected function getPublicKey(): string
    {
        return file_get_contents(Cfx::keyPath('cfx-public.key'));
    }

    /**
     * Returns the contents of the stored private key.
     *
     * @return false|string
     */
    protected function getPrivateKey()
    {
        return file_get_contents(Cfx::keyPath('cfx-private.key'));
    }

    /**
     * Parses the payload info on callback from base64 and decrypts using the payload using the RSA private key.
     *
     * @return mixed
     */
    protected function input()
    {
        $payload = base64_decode($this->request->input('payload'));

        openssl_private_decrypt($payload, $data, $this->getPrivateKey());

        return json_decode($data, true);
    }

    /**
     * Get the redirect request fields.
     *
     * @param $state
     * @return array
     */
    protected function getCodeFields($state = null): array
    {
        $scopes = array_merge(["session_info"], $this->getScopes());

        return [
            "auth_redirect" => $this->redirectUrl,
            "application_name" => config('cfx.app_name'),
            "client_id" => $this->clientId,
            "scopes" => $this->formatScopes($scopes, $this->scopeSeparator),
            "nonce" => $state,
            "public_key" => $this->getPublicKey(),
        ];
    }

    /**
     * Get the redirect authentication url.
     *
     * @param $state
     * @return string
     */
    protected function getAuthUrl($state): string
    {
        $baseUrl = self::$BASE_URL;

        return $this->buildAuthUrlFromBase("$baseUrl/user-api-key/new", $state);
    }

    /**
     * Creates a redirect response for authentication.
     *
     * @throws Exception
     */
    public function redirect(): RedirectResponse
    {
        $state = 'stateless';

        if ($this->usesState()) {
            $this->request->session()->put('state', $state = $this->getState());
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * Returns the authentication token from cfx.re
     *
     * @return array|ArrayAccess|mixed|string
     */
    protected function getCode()
    {
        return Arr::get($this->input(), 'key');
    }

    /**
     * Check if the state/ nonce in the callback is valid if stateful.
     *
     * @return bool
     */
    protected function hasInvalidState(): bool
    {
        if ($this->isStateless()) {
            return false;
        }

        $state = $this->request->session()->pull('state');

        return empty($state) || Arr::get($this->input(), 'nonce') !== $state;
    }

    /**
     * @return null
     */
    protected function getTokenUrl()
    {
        /*
         * Returns null because there is no code to access code exchange.
         * Function is implement because it is required by Socialite.
         */

        return null;
    }

    /**
     * Returns the parsed response data for socialite.
     *
     * @param $code
     * @return array
     */
    public function getAccessTokenResponse($code): array
    {
        /*
         * Returns the exchange token as access token.
         * Since CFX.re does not have a full OAuth flow the exchange token is the users API key and so the access token.
         * Remaining fields are only required by socialite and are not used.
         */

        return [
            'access_token' => $code,
            'refresh_token' =>  '',
            'expires_in' => '',
        ];
    }

    /**
     * Headers used for authenticating the API.
     *
     * @param $code
     * @return array
     */
    protected function getTokenHeaders($code): array
    {
        return [
            'Accept' => 'application/json',
            'User-Api-Key' => (string) $code,
            'User-Api-Client-Id' => $this->clientId,
        ];
    }

    /**
     * Gets the user information from the session endpoint.
     *
     * @param $token
     * @return array
     * @throws GuzzleException
     */
    protected function getCurrentUser($token): array
    {
        $baseUrl = self::$BASE_URL;

        $response = $this->getHttpClient()->get("$baseUrl/session/current.json", [
            RequestOptions::HEADERS => $this->getTokenHeaders($token),
        ]);

        return json_decode($response->getBody(), true)['current_user'];
    }

    /**
     * Gets the user information from the users endpoint.
     *
     * @param $token
     * @return array|mixed
     * @throws GuzzleException
     */
    protected function getUserByToken($token): mixed
    {
        $baseUrl = self::$BASE_URL;
        $username = $this->getCurrentUser($token)['username'];

        $response = $this->getHttpClient()->get("$baseUrl/users/$username.json", [
            RequestOptions::HEADERS => $this->getTokenHeaders($token)
        ]);

        return json_decode($response->getBody(), true)['user'];
    }

    /**
     * Format the avatar_template field to create a correct link
     *
     * @param array $user
     * @return string
     */
    protected function formatAvatar(array $user): string
    {
        $baseUrl = self::$BASE_URL;

        return $baseUrl . str_replace('{size}', config('cfx.avatar_size'), $user['avatar_template']);
    }

    /**
     * Map the returned data to a socialite user object.
     *
     * @param array $user
     * @return User
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['username'],
            'email' => $user['email'],
            'avatar' => $this->formatAvatar($user),
        ]);
    }
}
