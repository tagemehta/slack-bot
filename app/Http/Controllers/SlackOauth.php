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
        $result_body = json_decode($result->getBody());
        DB::insert('insert into slack_workspaces (team_id, user_token) values (?, ?)', [$result_body->team->id, $result_body->authed_user->access_token]);
        $bot_access_token = DB::select("select user_token from slack_workspaces where team_id=?", [$result_body->team->id])[0]->user_token;
        return $bot_access_token;


    }
}
