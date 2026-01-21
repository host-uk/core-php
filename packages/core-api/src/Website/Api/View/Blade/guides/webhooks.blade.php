@extends('api::layouts.docs')

@section('title', 'Webhooks')

@section('content')
<div class="flex">

    {{-- Sidebar --}}
    <aside class="hidden lg:block fixed left-0 top-16 md:top-20 bottom-0 w-64 border-r border-slate-200 dark:border-slate-800">
        <div class="h-full px-4 py-8 overflow-y-auto no-scrollbar">
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="#overview" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Overview
                        </a>
                    </li>
                    <li>
                        <a href="#setup" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Setup
                        </a>
                    </li>
                    <li>
                        <a href="#events" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Event Types
                        </a>
                    </li>
                    <li>
                        <a href="#payload" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Payload Format
                        </a>
                    </li>
                    <li>
                        <a href="#verification" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Verification
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="lg:pl-64 w-full">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">

            {{-- Breadcrumb --}}
            <nav class="mb-8">
                <ol class="flex items-center gap-2 text-sm">
                    <li><a href="{{ route('api.guides') }}" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Guides</a></li>
                    <li class="text-slate-400">/</li>
                    <li class="text-slate-800 dark:text-slate-200">Webhooks</li>
                </ol>
            </nav>

            <h1 class="h1 mb-4 text-slate-800 dark:text-slate-100">Webhooks</h1>
            <p class="text-xl text-slate-600 dark:text-slate-400 mb-12">
                Receive real-time notifications for events in your workspace.
            </p>

            {{-- Overview --}}
            <section id="overview" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Overview</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Webhooks allow your application to receive real-time HTTP callbacks when events occur in your Host UK workspace. Instead of polling the API, webhooks push data to your server as events happen.
                </p>
                <div class="text-sm p-4 bg-blue-50 border border-blue-200 rounded-sm dark:bg-blue-900/20 dark:border-blue-800">
                    <div class="flex items-start">
                        <svg class="fill-blue-500 shrink-0 mr-3 mt-0.5" width="16" height="16" viewBox="0 0 16 16">
                            <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm1 12H7V7h2v5zm0-6H7V4h2v2z"/>
                        </svg>
                        <p class="text-blue-800 dark:text-blue-200">
                            <strong>Coming soon:</strong> Webhook functionality is currently in development.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Setup --}}
            <section id="setup" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Setup</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    To configure webhooks:
                </p>
                <ol class="list-decimal list-inside space-y-2 text-slate-600 dark:text-slate-400">
                    <li>Go to <strong>Settings → Webhooks</strong> in your workspace</li>
                    <li>Click <strong>Add Webhook</strong></li>
                    <li>Enter your endpoint URL (must be HTTPS)</li>
                    <li>Select the events you want to receive</li>
                    <li>Save and note your webhook secret</li>
                </ol>
            </section>

            {{-- Events --}}
            <section id="events" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Event Types</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Available webhook events:
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left py-3 px-4 font-medium text-slate-800 dark:text-slate-200">Event</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-800 dark:text-slate-200">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">biolink.created</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">A new biolink was created</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">biolink.updated</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">A biolink was updated</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">biolink.deleted</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">A biolink was deleted</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">click.tracked</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">A link click was recorded</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Payload --}}
            <section id="payload" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Payload Format</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Webhook payloads are sent as JSON:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden">
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300">{
  <span class="text-blue-400">"id"</span>: <span class="text-green-400">"evt_abc123"</span>,
  <span class="text-blue-400">"type"</span>: <span class="text-green-400">"biolink.created"</span>,
  <span class="text-blue-400">"created_at"</span>: <span class="text-green-400">"2024-01-15T10:30:00Z"</span>,
  <span class="text-blue-400">"workspace_id"</span>: <span class="text-amber-400">1</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"id"</span>: <span class="text-amber-400">123</span>,
    <span class="text-blue-400">"url"</span>: <span class="text-green-400">"mypage"</span>,
    <span class="text-blue-400">"type"</span>: <span class="text-green-400">"biolink"</span>
  }
}</code></pre>
                </div>
            </section>

            {{-- Verification --}}
            <section id="verification" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Verification</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    All webhook requests include a signature header for verification:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden mb-4">
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300">X-Host-Signature: sha256=abc123...</code></pre>
                </div>

                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Verify the signature by computing HMAC-SHA256 of the request body using your webhook secret:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                        <span class="text-sm text-slate-400">PHP</span>
                    </div>
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300"><span class="text-purple-400">$signature</span> = <span class="text-teal-400">hash_hmac</span>(<span class="text-green-400">'sha256'</span>, <span class="text-purple-400">$requestBody</span>, <span class="text-purple-400">$webhookSecret</span>);
<span class="text-purple-400">$valid</span> = <span class="text-teal-400">hash_equals</span>(<span class="text-green-400">'sha256='</span> . <span class="text-purple-400">$signature</span>, <span class="text-purple-400">$headerSignature</span>);</code></pre>
                </div>
            </section>

            {{-- Next steps --}}
            <div class="flex items-center justify-between pt-8 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('api.guides.qrcodes') }}" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">
                    &larr; QR Code Generation
                </a>
                <a href="{{ route('api.guides.errors') }}" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 font-medium">
                    Error Handling &rarr;
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
