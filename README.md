# Cfx.re Socialite 

[Cfx.re](https://forum.cfx.re/) Laravel [socialite](https://laravel.com/docs/9.x/socialite) adapter for authenticating with [FiveM](https://fivem.net) and [RedM](https://redm.net).

## Table of Contents

- [Requirement](#requirements)
- [Installation](#installation)
- [Scopes](#scopes)
- [Disclaimer](#disclaimer)

## Requirements

- [Laravel](https://laravel.com/) 9+

## Installation

- Install dependencies `composer require thecoati/socialite-cfx-provider`.
- Generate RSA public and private key `php artisan cfx:keys`.
- Add the following lines to your `.env`:
```dotenv
CFX_APP_NAME=Laravel
CFX_CLIENT_ID=UNIQUE_CLIENT_ID
CFX_REDIRECT_URL=http://127.0.0.1:8000/callback
CFX_AVATAR_SIZE=128
```
Alternatively, you can also publish the configuration file using `php artisan vendor:publish --tag=cfx-config`.

The `CFX_APP_NAME` is the application name listed at the [apps page](https://forum.cfx.re/u/sarahwinter/preferences/apps). \
Make sure to generate a **random* and **unique** `CFX_CLIENT_ID` you can generate this key yourself. \
The `CFX_AVATAR_SIZE` determines the size at which the avatar is returned. Please note that values lower than 8 and higher than 1000 will result in a blank avatar. This is because Discourse has hardcoded these limits in their avatar controller. \
Unless a real OAuth flow Discourse forums do not require any way of application registration.

## Scopes

Available Discourse user API key scopes.\
https://github.com/discourse/discourse/blob/main/app/models/user_api_key_scope.rb
- `read`
- `write`
- `message_bus`
- `push`
- `one_time_password`
- `notifications`
- `session_info` *Required
- `bookmarks_calendar`

## Disclaimer

Unfortunately Discourse (community software used on forum.cfx.re) does not provide a full OAuth2 flow. \
We modified the authentication flow of Socialite to match the authentication flow of Discourse. \
Therefore It might be possible that Socialite features behave differently than expected. \
Also note that there is **no email scope** available to obtain the users email.

For more information on the Discourse user API authentication see: \
https://meta.discourse.org/t/user-api-keys-specification/48536
