<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderSource;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Halaman "Kanal online": status integrasi tiap platform. */
class ChannelController extends Controller
{
    public function index(): View
    {
        // true jika webhook secret platform sudah diisi di .env
        $channelStatus = collect(OrderSource::marketplaces())->mapWithKeys(
            fn (OrderSource $source) => [
                $source->slug() => filled(config('ruangkita.channels.' . $source->slug() . '.webhook_secret')),
            ]
        )->all();

        return view('admin.channels', compact('channelStatus'));
    }
}
