<?php

namespace App\Http\Middleware;

use App\Models\Form;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePublicFormEmbedPolicy
{
    /**
     * Send `X-Frame-Options: DENY` for public form pages whose author
     * has disabled embedding.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $team = $request->route('team');
        $formSlug = $request->route('formSlug');

        if ($team === null || $formSlug === null) {
            return $response;
        }

        $form = Form::query()
            ->where('slug', $formSlug)
            ->whereHas('project', fn ($query) => $query->where('team_id', $team->id))
            ->first();

        if ($form !== null && ! $form->settings->embed->allowEmbedding) {
            $response->headers->set('X-Frame-Options', 'DENY');
        }

        return $response;
    }
}
