<?php

namespace App\Traits;

trait AFhelper {

    protected $arr_month = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    protected int $afYear;
    protected int $afMonth;
    protected string $afMonthLabel;

    public function afSetYearMonth(string $dateYMD) {
        $a = explode('-', $dateYMD, 3);
        $bln = intval($a[1]);
        $this->afYear = intval($a[0]);
        $this->afMonth = $bln;
        $this->afMonthLabel = $this->arr_month[$bln];
    }

}
