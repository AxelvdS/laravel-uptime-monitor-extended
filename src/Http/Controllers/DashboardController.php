<?php

namespace AxelvdS\UptimeMonitorExtended\Http\Controllers;

use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\UpDownWidget;
use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\DevicesDownTable;
use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\UptimeGraph;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected UpDownWidget $upDownWidget;
    protected DevicesDownTable $devicesDownTable;
    protected UptimeGraph $uptimeGraph;

    public function __construct(
        UpDownWidget $upDownWidget,
        DevicesDownTable $devicesDownTable,
        UptimeGraph $uptimeGraph
    ) {
        $this->upDownWidget = $upDownWidget;
        $this->devicesDownTable = $devicesDownTable;
        $this->uptimeGraph = $uptimeGraph;
    }

    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        return view('uptime-monitor-extended::dashboard');
    }

    /**
     * Get up/down statistics.
     */
    public function upDownStats(): JsonResponse
    {
        return response()->json($this->upDownWidget->getData());
    }

    /**
     * Get devices down table data.
     */
    public function devicesDown(): JsonResponse
    {
        $limit = request()->get('limit', 10);
        return response()->json($this->devicesDownTable->getData($limit));
    }

    /**
     * Get uptime graph data.
     */
    public function uptimeGraph(): JsonResponse
    {
        $hours = request()->get('hours', 24);
        $intervalMinutes = request()->get('interval', 60);
        return response()->json($this->uptimeGraph->getData($hours, $intervalMinutes));
    }
}

