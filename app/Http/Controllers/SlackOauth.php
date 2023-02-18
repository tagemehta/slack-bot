<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\retrieveToken;

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
        Log::debug($code);
        $fParams = [
            "form_params" => [
                "code" => $code,
                "client_id" => env("SLACK_CLIENT_ID"),
                "client_secret" => env("SLACK_CLIENT_SECRET"),
                "redirect_uri" => env("SLACK_REDIRECT_URI")
            ]
            ];
            Log::debug($fParams);
        $result = $client->post("oauth.v2.access", $fParams);
        Log::debug(print_r(json_decode($result->getBody()), true));
        $result_body = json_decode($result->getBody());
        $team_id =  $result_body->team->id;
        $bot_token = $result_body->access_token;
        DB::table("slack_workspaces")->upsert([
            ["team_id" => $team_id, "bot_token" => strval($bot_token)]
        ], ["team_id"], ["bot_token"]);
    
        $bot_access_token = DB::select("select bot_token from slack_workspaces where team_id=?", [$team_id])[0]->bot_token;
        if ($bot_access_token) {
            return "success";
        } else {
            return "failure";
        }


    }
}
