@extends('api::layouts.docs')

@section('title', 'Managing Biolinks')

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
                        <a href="#create-biolink" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Create a Biolink
                        </a>
                    </li>
                    <li>
                        <a href="#add-blocks" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Add Blocks
                        </a>
                    </li>
                    <li>
                        <a href="#block-types" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Block Types
                        </a>
                    </li>
                    <li>
                        <a href="#update-biolink" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Update Settings
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
                    <li class="text-slate-800 dark:text-slate-200">Managing Biolinks</li>
                </ol>
            </nav>

            <h1 class="h1 mb-4 text-slate-800 dark:text-slate-100">Managing Biolinks</h1>
            <p class="text-xl text-slate-600 dark:text-slate-400 mb-12">
                Create, update, and manage biolink pages with blocks and themes.
            </p>

            {{-- Overview --}}
            <section id="overview" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Overview</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Biolinks are customisable landing pages hosted at <code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-sm">lt.hn/yourpage</code>. Each biolink can contain multiple blocks of content, including links, text, images, and more.
                </p>
            </section>

            {{-- Create Biolink --}}
            <section id="create-biolink" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Create a Biolink</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Create a new biolink page with a POST request:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden mb-4">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                        <span class="text-sm text-slate-400">cURL</span>
                    </div>
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300"><span class="text-teal-400">curl</span> <span class="text-slate-500">--request</span> POST \
  <span class="text-slate-500">--url</span> <span class="text-amber-400">'https://api.host.uk.com/api/v1/bio'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Authorization: Bearer YOUR_API_KEY'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Content-Type: application/json'</span> \
  <span class="text-slate-500">--data</span> <span class="text-amber-400">'{
    "url": "mypage",
    "type": "biolink"
  }'</span></code></pre>
                </div>

                <p class="text-slate-600 dark:text-slate-400">
                    The <code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-sm">url</code> field determines your biolink's address. It must be unique within your workspace.
                </p>
            </section>

            {{-- Add Blocks --}}
            <section id="add-blocks" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Add Blocks</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Add content blocks to your biolink:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden mb-4">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                        <span class="text-sm text-slate-400">cURL</span>
                    </div>
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300"><span class="text-teal-400">curl</span> <span class="text-slate-500">--request</span> POST \
  <span class="text-slate-500">--url</span> <span class="text-amber-400">'https://api.host.uk.com/api/v1/bio/1/blocks'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Authorization: Bearer YOUR_API_KEY'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Content-Type: application/json'</span> \
  <span class="text-slate-500">--data</span> <span class="text-amber-400">'{
    "type": "link",
    "data": {
      "title": "Visit My Website",
      "url": "https://example.com"
    }
  }'</span></code></pre>
                </div>
            </section>

            {{-- Block Types --}}
            <section id="block-types" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Block Types</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Available block types:
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left py-3 px-4 font-medium text-slate-800 dark:text-slate-200">Type</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-800 dark:text-slate-200">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">link</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Clickable link button</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">text</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Text paragraph or heading</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">image</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Image with optional link</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">socials</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Social media icon links</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4"><code class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs">divider</code></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Visual separator</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Update Biolink --}}
            <section id="update-biolink" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Update Settings</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Update biolink settings like URL, theme, or metadata:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                        <span class="text-sm text-slate-400">cURL</span>
                    </div>
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300"><span class="text-teal-400">curl</span> <span class="text-slate-500">--request</span> PUT \
  <span class="text-slate-500">--url</span> <span class="text-amber-400">'https://api.host.uk.com/api/v1/bio/1'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Authorization: Bearer YOUR_API_KEY'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Content-Type: application/json'</span> \
  <span class="text-slate-500">--data</span> <span class="text-amber-400">'{
    "url": "newpage",
    "title": "My Updated Page"
  }'</span></code></pre>
                </div>
            </section>

            {{-- Next steps --}}
            <div class="flex items-center justify-between pt-8 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('api.guides.authentication') }}" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">
                    &larr; Authentication
                </a>
                <a href="{{ route('api.guides.qrcodes') }}" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 font-medium">
                    QR Code Generation &rarr;
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
