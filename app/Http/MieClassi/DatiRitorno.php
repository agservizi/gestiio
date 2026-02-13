<?php

namespace App\Http\MieClassi;

class DatiRitorno
{
    protected $datiRitornoArray = [
        'success' => true
    ];

    /**
     * @param bool $bool
     * @return DatiRitorno
     */
    public function success($bool)
    {
        $this->datiRitornoArray = array_merge($this->datiRitornoArray, ['success' => $bool]);
        return $this;
    }

    /**
     * @param bool $bool
     * @return DatiRitorno
     */
    public function chiudiDialog($bool)
    {
        $this->datiRitornoArray = array_merge($this->datiRitornoArray, ['chiudiDialog' => $bool]);
        return $this;

    }


    /**
     * @param bool $bool
     * @return DatiRitorno
     */
    public function redirect($url)
    {
        $this->datiRitornoArray = array_merge($this->datiRitornoArray, ['redirect' => $url]);
        return $this;

    }

    /**
     * @param int $id
     * @return DatiRitorno
     */
    public function id($id)
    {
        $this->datiRitornoArray = array_merge($this->datiRitornoArray, ['id' => $id]);
        return $this;

    }

    /**
     * @param bool $bool
     * @return DatiRitorno
     */
    public function oggettoReload($idOggettoSenzaCancelletto, $html)
    {
        $this->datiRitornoArray = array_merge($this->datiRitornoArray, ['oggettoReload' => $idOggettoSenzaCancelletto, 'html' => base64_encode($html)]);
        return $this;

    }

    /**
     * @param bool $bool
     * @return DatiRitorno
     */
    public function rimuoviOggetto($idOggettoConCacelletto)
    {
        $this->datiRitornoArray = array_merge($this->datiRitornoArray, ['rimuoviOggetto' => $idOggettoConCacelletto]);
        return $this;

    }


    public function keyValue($key,$value){
        $this->datiRitornoArray = array_merge($this->datiRitornoArray, [$key =>$value]);
        return $this;

    }

    /**
     * @return array
     */
    public function getArray()
    {
        return $this->datiRitornoArray;
    }


}
