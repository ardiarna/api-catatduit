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

    public function matDMYtime(string $datestring) {
        $date = date_create($datestring);
        return date_format($date, "d/m/Y H:i");
    }

    public function matCurrency(string $nominal) {
        return 'Rp. '.number_format($nominal, 0, "," ,".");
    }

    public function afSendFCMessaging(array $receiver, string $title, string $body, string $halaman = '', string $nomor = '', string $image = '') {
        if(empty($receiver)) {
            return false;
        }
        $header = array(
            'Content-type: application/json',
            'Authorization: key=' . env('FCM_KEY')
        );

        $fcmMsg = array(
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
        );
        if($image != '') {
            $fcmMsg['image'] = $image;
        }

        $fcmFields = array(
            'registration_ids' => $receiver,
            'priority' => 'high',
            'notification' => $fcmMsg
        );
        if($halaman != '') {
            $fcmFields['data'] = array(
                'halaman' => $halaman,
                'nomor' => $nomor,
            );
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($curl, CURLOPT_POST, true );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( $fcmFields ) );
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }



}
