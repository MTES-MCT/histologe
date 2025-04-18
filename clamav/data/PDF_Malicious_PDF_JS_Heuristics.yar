rule Malicious_PDF_JS_Heuristics
{
    meta:
        description = "Detects obfuscated or suspicious JavaScript in PDFs, including various eval/unescape variants"

    strings:
        $pdf_tag1 = /\x25\x50\x44\x46\x2d/
        $js_tag = /\/(JavaScript|JS)/

        // Méthodes suspectes directes
        $eval = "eval"
        $unescape = "unescape"

        // Méthodes équivalentes ou indirectes
        $function_ctor = "Function("
        $setTimeout = /setTimeout\s*\(/
        $setInterval = /setInterval\s*\(/
        $fromCharCode = "String.fromCharCode"
        $decodeURIComponent = "decodeURIComponent"
        $atob = "atob"

        // Encodage
        $large_encoded = /(\\x[0-9a-fA-F]{2}){10,}/
        $unicode_encoded = /(\\u[0-9a-fA-F]{4}){5,}/

        // Bloc PDF stream
        $stream = /stream[\r\n]+.{1,10000}endstream/s
    condition:
        $pdf_tag1 in (0..1024) and
        $js_tag and
        (any of (
            $eval, $unescape, $function_ctor, $setTimeout,
            $setInterval, $fromCharCode, $decodeURIComponent,
            $atob, $large_encoded, $unicode_encoded, $stream
        ))
}
