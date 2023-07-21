<?php

namespace App\Repositories;

interface CeklistDetilRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function updateNama($id, $nama);
    public function updateIsceklist($id, $isceklist);
    public function updateIsaktif($id, $isaktif);
    public function delete($id);
    public function deletesByCeklistId($ceklist_id);
}
