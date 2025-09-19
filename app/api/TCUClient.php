<?php
declare(strict_types=1);

class TCUClient
{
    /**
     * @return string|array<string,mixed>
     */
    public static function getCertidaoPdf(string $cnpj): string|array
    {
        $cnpj = preg_replace('/\D+/', '', (string)$cnpj);
        if (strlen($cnpj) !== 14) return ['__error' => 'CNPJ inválido'];
        $url = "https://certidoes-apf.apps.tcu.gov.br/api/rest/publico/certidoes/{$cnpj}?seEmitirPDF=true";

        // Try direct PDF
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ["Accept: application/pdf"]
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ct   = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err  = curl_error($ch);
        curl_close($ch);
        $isPdf = $resp !== false && $code === 200 && (stripos($ct, 'application/pdf') !== false || str_starts_with((string)$resp, '%PDF'));
        if ($isPdf) return $resp;

        // Fallback JSON
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ["Accept: application/json"]
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err2 = curl_error($ch);
        curl_close($ch);
        if ($resp !== false && $code === 200) {
            $j = json_decode($resp, true);
            $b64 = null;
            $paths = ['certidaoPDF','pdfEmissaoBase64','arquivoBase64','pdfBase64','certidaoPdfBase64','arquivo','conteudo','bytes'];
            foreach ($paths as $p) {
                if (isset($j[$p]) && is_string($j[$p]) && strlen($j[$p]) > 100) { $b64 = $j[$p]; break; }
            }
            if (!$b64 && is_array($j)) {
                array_walk_recursive($j, function($v) use (&$b64){ if(!$b64 && is_string($v) && strlen($v)>200 && preg_match('~^[A-Za-z0-9/+=\r\n]+$~',$v)) $b64=$v; });
            }
            if ($b64) {
                $pdf = base64_decode($b64, true);
                if ($pdf !== false && str_starts_with($pdf, '%PDF')) return $pdf;
            }
        }
        return ['__error' => 'Falha TCU', '__debug' => [$code, $err, isset($err2) ? $err2 : null]];
    }
}
