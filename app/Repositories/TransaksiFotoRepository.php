<?php

namespace App\Repositories;

interface TransaksiFotoRepository {
    public function findAll($transaksi_id);
    public function upsert($nama, $transaksi_id);
    public function delete($nama);
}
