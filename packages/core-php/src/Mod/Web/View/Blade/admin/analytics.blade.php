<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('hub.bio.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('web::web.analytics.title') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">/{{ $biolink->url }}</p>
                </div>
            </div>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            {{-- Period selector --}}
            <select
                wire:model.live="period"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
            >
                <option value="24h">{{ __('web::web.analytics.period.24h') }}</option>
                <option value="7d">{{ __('web::web.analytics.period.7d') }}</option>
                <option value="30d">{{ __('web::web.analytics.period.30d') }}</option>
                <option value="90d">{{ __('web::web.analytics.period.90d') }}</option>
                <option value="1y">{{ __('web::web.analytics.period.1y') }}</option>
            </select>
            <a
                href="{{ route('hub.bio.edit', $biolink->id) }}"
                wire:navigate
                class="btn border-gray-300 dark:border-gray-600 hover:border-violet-500 text-gray-700 dark:text-gray-300"
            >
                <i class="fa-solid fa-pen-to-square mr-2"></i> {{ __('web::web.actions.edit') }}
            </a>
        </div>
    </div>

    {{-- Stats cards --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    <div class="grid grid-cols-2 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-violet-100 dark:bg-violet-900/30 rounded-lg">
                    <i class="fa-solid fa-mouse-pointer text-violet-600 dark:text-violet-400 text-xl"></i>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('web::web.analytics.stats.total_clicks') }}</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->stats['clicks']) }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                    <i class="fa-solid fa-users text-cyan-600 dark:text-cyan-400 text-xl"></i>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('web::web.analytics.stats.unique_clicks') }}</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->stats['unique_clicks']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Clicks over time chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.chart.title') }}</h2>
        @if(count($this->chartData['labels']) > 0 && array_sum($this->chartData['clicks']) > 0)
            <div class="h-64" x-data="{
                chart: null,
                init() {
                    this.renderChart();
                    this.$watch('$wire.period', () => {
                        this.$nextTick(() => this.renderChart());
                    });
                },
                renderChart() {
                    if (this.chart) {
                        this.chart.destroy();
                    }
                    const ctx = this.$refs.canvas.getContext('2d');
                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @js($this->chartData['labels']),
                            datasets: [{
                                label: '{{ __('web::web.analytics.chart.clicks_label') }}',
                                data: @js($this->chartData['clicks']),
                                borderColor: '#8b5cf6',
                                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                fill: true,
                                tension: 0.4
                            }, {
                                label: '{{ __('web::web.analytics.chart.unique_label') }}',
                                data: @js($this->chartData['unique_clicks']),
                                borderColor: '#06b6d4',
                                backgroundColor: 'transparent',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            }">
                <canvas x-ref="canvas"></canvas>
            </div>
        @else
            <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <i class="fa-solid fa-chart-line text-4xl mb-2 opacity-50"></i>
                    <p>{{ __('web::web.analytics.chart.empty') }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Geographic and Referrer row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Countries --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.sections.countries') }}</h2>
            @if(count($this->countries) > 0)
                <div class="space-y-3">
                    @foreach($this->countries as $country)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <span class="text-lg">{{ $this->getFlagEmoji($country['country_code']) }}</span>
                                <span class="text-gray-900 dark:text-gray-100 truncate">{{ $country['country_name'] }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($country['clicks']) }}</span>
                                <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">{{ __('web::web.analytics.clicks') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">{{ __('web::web.analytics.empty.no_geographic') }}</p>
            @endif
        </div>

        {{-- Referrers --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.sections.referrers') }}</h2>
            @if(count($this->referrers) > 0)
                <div class="space-y-3">
                    @foreach($this->referrers as $ref)
                        <div class="flex items-center justify-between">
                            <div class="truncate flex-1 mr-4">
                                <span class="text-gray-900 dark:text-gray-100">{{ $ref['referrer'] }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($ref['clicks']) }}</span>
                                <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">{{ __('web::web.analytics.clicks') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">{{ __('web::web.analytics.empty.no_referrers') }}</p>
            @endif
        </div>
    </div>

    {{-- Device breakdown row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Devices --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.sections.devices') }}</h2>
            @if(count($this->devices) > 0)
                <div class="space-y-3">
                    @foreach($this->devices as $device)
                        @php
                            $total = array_sum(array_column($this->devices, 'clicks'));
                            $percentage = $total > 0 ? round(($device['clicks'] / $total) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid {{ $this->getDeviceIcon($device['device_type']) }} text-gray-400 w-4"></i>
                                    <span class="text-gray-900 dark:text-gray-100">{{ $device['label'] }}</span>
                                </div>
                                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $percentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div
                                    class="h-2 rounded-full"
                                    style="width: {{ $percentage }}%; background-color: {{ $this->getDeviceColour($device['device_type']) }}"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('web::web.analytics.empty.no_data') }}</p>
            @endif
        </div>

        {{-- Browsers --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.sections.browsers') }}</h2>
            @if(count($this->browsers) > 0)
                <div class="space-y-3">
                    @foreach($this->browsers as $browser)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-900 dark:text-gray-100">{{ $browser['browser'] ?? 'Unknown' }}</span>
                            <span class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($browser['clicks']) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('web::web.analytics.empty.no_data') }}</p>
            @endif
        </div>

        {{-- Operating Systems --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.sections.operating_systems') }}</h2>
            @if(count($this->operatingSystems) > 0)
                <div class="space-y-3">
                    @foreach($this->operatingSystems as $os)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-900 dark:text-gray-100">{{ $os['os'] ?? 'Unknown' }}</span>
                            <span class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($os['clicks']) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('web::web.analytics.empty.no_data') }}</p>
            @endif
        </div>
    </div>

    {{-- Block clicks --}}
    @if(count($this->blockClicks) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.sections.block_clicks') }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.analytics.table.block') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.analytics.table.type') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.analytics.table.clicks') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.analytics.table.unique') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->blockClicks as $block)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <div class="text-gray-900 dark:text-gray-100 max-w-xs truncate" title="{{ $block['label'] }}">
                                        {{ Str::limit($block['label'], 50) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge color="zinc">{{ str_replace('_', ' ', ucfirst($block['type'])) }}</flux:badge>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">
                                    {{ number_format($block['clicks']) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                    {{ number_format($block['unique_clicks']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- UTM Tracking row --}}
    @if(count($this->utmSources) > 0 || count($this->utmCampaigns) > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- UTM Sources --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.sections.utm_sources') }}</h2>
                @if(count($this->utmSources) > 0)
                    <div class="space-y-3">
                        @foreach($this->utmSources as $source)
                            <div class="flex items-center justify-between">
                                <div class="truncate flex-1 mr-4">
                                    <span class="text-gray-900 dark:text-gray-100">{{ $source['source'] }}</span>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($source['clicks']) }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">{{ __('web::web.analytics.clicks') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('web::web.analytics.empty.no_utm_sources') }}</p>
                @endif
            </div>

            {{-- UTM Campaigns --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('web::web.analytics.sections.utm_campaigns') }}</h2>
                @if(count($this->utmCampaigns) > 0)
                    <div class="space-y-3">
                        @foreach($this->utmCampaigns as $campaign)
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0 mr-4">
                                    <div class="text-gray-900 dark:text-gray-100 truncate">{{ $campaign['campaign'] }}</div>
                                    @if($campaign['source'] || $campaign['medium'])
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $campaign['source'] }}{{ $campaign['medium'] ? ' / ' . $campaign['medium'] : '' }}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($campaign['clicks']) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('web::web.analytics.empty.no_campaigns') }}</p>
                @endif
            </div>
        </div>
    @endif
    </div>
    {{-- Loading indicator --}}
    <div wire:loading class="flex justify-center py-8">
        <flux:icon name="arrow-path" class="size-6 animate-spin text-violet-500" />
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
