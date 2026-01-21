<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">BioHost Analytics</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Overview of all your biolinks performance</p>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            {{-- Period selector --}}
            <flux:dropdown align="end">
                <flux:button icon="calendar" variant="ghost">
                    {{ $this->periodLabel }}
                </flux:button>
                <flux:menu>
                    @foreach($this->availablePeriods as $key => $config)
                        <flux:menu.item
                            wire:click="$set('period', '{{ $key }}')"
                            :disabled="!$config['available']"
                            :class="$period === $key ? 'bg-violet-50 dark:bg-violet-900/20' : ''"
                        >
                            <div class="flex items-center justify-between w-full">
                                <span>{{ $config['label'] }}</span>
                                @if($config['requires_upgrade'])
                                    <flux:icon name="crown" class="text-amber-500 size-3" />
                                @endif
                            </div>
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Retention limit banner --}}
    @if($isDateLimited)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-crown text-amber-500"></i>
                <div class="flex-1">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        Analytics data is limited to {{ $maxRetentionDays }} days on your current plan.
                        <a href="{{ route('hub.billing') }}" class="underline font-medium">Upgrade for extended history.</a>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Summary stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Clicks</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ number_format($this->totalStats['clicks']) }}
                    </p>
                </div>
                <div class="p-3 rounded-full bg-violet-100 dark:bg-violet-900/30">
                    <i class="fa-solid fa-mouse-pointer text-xl text-violet-500"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Unique Visitors</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ number_format($this->totalStats['unique_clicks']) }}
                    </p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <i class="fa-solid fa-users text-xl text-blue-500"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Biolinks</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ number_format($this->totalStats['biolinks']) }}
                    </p>
                </div>
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                    <i class="fa-solid fa-link text-xl text-green-500"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Clicks over time chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Clicks Over Time</h2>
        <div class="h-64" x-data="chartComponent(@js($this->chartData))">
            <canvas x-ref="chart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Top biolinks --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Top Performing Biolinks</h2>
            @if(count($this->topBiolinks) > 0)
                <div class="space-y-3">
                    @foreach($this->topBiolinks as $index => $biolink)
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-300">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('hub.bio.analytics', $biolink['id']) }}" wire:navigate class="text-gray-900 dark:text-gray-100 font-medium hover:text-violet-500 truncate block">
                                    /{{ $biolink['url'] }}
                                </a>
                                <div class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ $biolink['type'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($biolink['clicks']) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">clicks</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">No click data available for this period.</p>
            @endif
        </div>

        {{-- Top countries --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Top Countries</h2>
            @if(count($this->countries) > 0)
                <div class="space-y-3">
                    @foreach($this->countries as $country)
                        <div class="flex items-center gap-3">
                            <span class="text-xl">{{ $this->getFlagEmoji($country['country_code']) }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-gray-900 dark:text-gray-100 font-medium truncate">{{ $country['country_name'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($country['clicks']) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($country['unique_clicks']) }} unique</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">No geographic data available for this period.</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Devices --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Devices</h2>
            @if(count($this->devices) > 0)
                <div class="space-y-4">
                    @php
                        $totalDeviceClicks = collect($this->devices)->sum('clicks');
                    @endphp
                    @foreach($this->devices as $device)
                        @php
                            $percentage = $totalDeviceClicks > 0 ? ($device['clicks'] / $totalDeviceClicks) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid {{ $this->getDeviceIcon($device['device_type'] ?? 'unknown') }} text-gray-500 dark:text-gray-400"></i>
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $device['label'] }}</span>
                                </div>
                                <span class="text-gray-600 dark:text-gray-300">{{ number_format($device['clicks']) }} ({{ round($percentage) }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full" style="width: {{ $percentage }}%; background-color: {{ $this->getDeviceColour($device['device_type'] ?? 'unknown') }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">No device data available for this period.</p>
            @endif
        </div>

        {{-- Referrers --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Top Referrers</h2>
            @if(count($this->referrers) > 0)
                <div class="space-y-3">
                    @foreach($this->referrers as $referrer)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                @if($referrer['referrer'] === 'Direct / None')
                                    <i class="fa-solid fa-arrow-right text-gray-500 dark:text-gray-400 text-sm"></i>
                                @else
                                    <i class="fa-solid fa-globe text-gray-500 dark:text-gray-400 text-sm"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-gray-900 dark:text-gray-100 font-medium truncate">{{ $referrer['referrer'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($referrer['clicks']) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">clicks</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">No referrer data available for this period.</p>
            @endif
        </div>
    </div>
</div>

@script
<script>
Alpine.data('chartComponent', (data) => ({
    chart: null,
    init() {
        this.$nextTick(() => {
            this.renderChart(data);
        });

        Livewire.on('chartDataUpdated', (newData) => {
            this.renderChart(newData);
        });
    },
    renderChart(chartData) {
        if (this.chart) {
            this.chart.destroy();
        }

        const ctx = this.$refs.chart.getContext('2d');
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels || [],
                datasets: [
                    {
                        label: 'Total Clicks',
                        data: chartData.clicks || [],
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.3,
                    },
                    {
                        label: 'Unique Visitors',
                        data: chartData.unique_clicks || [],
                        borderColor: '#06b6d4',
                        backgroundColor: 'rgba(6, 182, 212, 0.1)',
                        fill: true,
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
}));
</script>
@endscript
