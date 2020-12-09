<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class SlackOauth extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $client = new Client([
            "base_uri" => "https://slack.com/api/"
        ]);
        $code = $request->query('code');
        $result = $client->post("oauth.v2.access", [
            "form_params" => [
                "code" => $code,
                "client_id" => env("SLACK_CLIENT_ID"),
                "client_secret" => env("SLACK_CLIENT_SECRET"),
                "redirect_uri" => env("SLACK_REDIRECT_URI")
            ]
        ]);
        Log::critical(print_r(json_decode($result->getBody()), true));
        session(["bot_token" => json_decode($result->getBody())->access_token]);
        return(response(session('bot_token')));

    }
}
