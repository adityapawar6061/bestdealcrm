<?php
namespace Controllers;

class TestUploadController extends BaseController
{
    /**
     * Generate a test PDF file
     */
    public function testPdf(): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="test_document.pdf"');

        // Minimal valid PDF
        $content = "%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792]
   /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length 189 >>
stream
BT
/F1 24 Tf
50 700 Td
(Test Document) Tj
/F1 14 Tf
0 -40 Td
(BestDeal CRM - Test Upload) Tj
0 -30 Td
(Date: " . date('Y-m-d H:i:s') . ") Tj
0 -30 Td
(This is an auto-generated test PDF.) Tj
0 -30 Td
(Created for form upload testing.) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000266 00000 n 
0000000507 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
584
%%EOF";

        echo $content;
        exit;
    }

    /**
     * Generate a test JPG image
     */
    public function testImage(): void
    {
        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename="test_document.jpg"');

        $width = 400;
        $height = 300;
        $img = imagecreatetruecolor($width, $height);

        // Background
        $bg = imagecolorallocate($img, 240, 240, 245);
        imagefill($img, 0, 0, $bg);

        // Border
        $border = imagecolorallocate($img, 100, 100, 200);
        imagerectangle($img, 2, 2, $width-3, $height-3, $border);

        // Header bar
        $headerBg = imagecolorallocate($img, 30, 30, 80);
        imagefilledrectangle($img, 0, 0, $width, 60, $headerBg);

        // Header text
        $white = imagecolorallocate($img, 255, 255, 255);
        imagestring($img, 5, 20, 20, "BESTDEAL CRM - TEST DOCUMENT", $white);

        // Body text
        $dark = imagecolorallocate($img, 50, 50, 50);
        $gray = imagecolorallocate($img, 100, 100, 100);
        imagestring($img, 4, 30, 90, "Document Type: Test Upload", $dark);
        imagestring($img, 4, 30, 115, "Generated: " . date('Y-m-d H:i:s'), $dark);
        imagestring($img, 4, 30, 140, "Purpose: Form Upload Testing", $dark);
        imagestring($img, 3, 30, 180, "This is an auto-generated test image", $gray);
        imagestring($img, 3, 30, 200, "created for form field upload testing.", $gray);
        imagestring($img, 3, 30, 240, "File types: PAN Card, Salary Slip,", $gray);
        imagestring($img, 3, 30, 260, "Bank Statement, Form 16, etc.", $gray);

        imagejpeg($img, null, 85);
        imagedestroy($img);
        exit;
    }
}
