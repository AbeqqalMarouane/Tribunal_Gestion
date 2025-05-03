<?php
ob_start(); // Start output buffering
include('dbConn.php');

$ID_Conge = $_GET['ID_Conge']; 
// Fetch data from the "conge" table
$sql = "SELECT * FROM conge WHERE ID_Conge = :ID_Conge";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':ID_Conge', $ID_Conge, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if data exists
if (!$row) {
    die("لم يتم العثور على البيانات المطلوبة.");
}

// Retrieve data from the fetched row
$type_cont = $row["ID_contrat"];
$duree = $row["Duree_Conge"];
$remarque = $row["Remarque"];
$id_p = $row["ID_user"];

// Determine type of "morfa9at" and "al3amal"
$sqlTypeContrat = "SELECT Type_Contrat FROM contrat WHERE ID_Contrat = :ID_Contrat";
$stmtTypeContrat = $dbh->prepare($sqlTypeContrat);
$stmtTypeContrat->bindParam(':ID_Contrat', $type_cont, PDO::PARAM_INT);
$stmtTypeContrat->execute();
$typeContratRow = $stmtTypeContrat->fetch(PDO::FETCH_ASSOC);

$type_cont_name = $typeContratRow["Type_Contrat"];

if ($type_cont_name === "ادارية") {
    $morfa9at = 1;
    $al3amal = " عمل";
} else {
    $morfa9at = 2;
    $al3amal = "";
}

// Calculate the year for the note
$currentMonth = date('n');
$currentYear = date('Y');
$year = ($currentMonth >= 6) ? $currentYear : $currentYear - 1;

// Fetch user data
$sql = "SELECT * FROM users WHERE ID_user = :ID_user";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':ID_user', $id_p, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user data exists
if (!$row) {
    die("لم يتم العثور على بيانات المستخدم.");
}

// Retrieve user data from the fetched row
$nom = $row["Nom"];
$prenom = $row["Prenom"];
$trav = $row["Metier"];
$masla7a = ($row["Departement"] === "مصلحة المنتدبين القضائيين والأطر التقنية") ? "مصلحة المنتدبين القضائيين و الاطر التقنية" : "مصلحة كتاب الضبط والمحررين القضائيين";

// Include the TCPDF library
require_once('../tcpdf-main/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('STAGE');
$pdf->SetTitle('HTML to PDF');
$pdf->SetSubject('Generating PDF from HTML using TCPDF');
$pdf->SetKeywords('TCPDF, PDF, HTML');

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set auto page breaks
$pdf->SetAutoPageBreak(false, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set font to Arial
$pdf->SetFont('arial', '', 12);

// Add a page
$pdf->AddPage();

// Get the current date
$current_date = date('d/m/Y');

// Define HTML content with table
$html = '
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تصدير إلى PDF</title>
  <style>
    img {
      height: 5em;
      width: 5em;
    }
    td {
        text-align: center;
    }
  </style>
</head>
<body>
  <div id="pdf-content">
    <table style="width: 100%; border: none; padding-bottom: 20px;">
      <tr>
        <td style="text-align: left; width: 30%; vertical-align: top;">
        فاس في :
        ' . $current_date . '
        </td>
        <td style="text-align: center; width: 40%; vertical-align: top;">
          <div>
            <img style="height: 5em; width: 5em;" src="../images/logo.png" alt="صورة توضيحية">
          </div>
        </td>
        <td style="text-align: right; width: 30%; vertical-align: top; line-height: 1.2;">
        <div class="my-divv">
          المملكة المغربية<br>
          وزارة العدل<br>
          محكمة الاستئناف بفاس<br>
          محكمة الابتدائية بفاس<br>
          النيابة العامة<br>
          </div>
        </td>
      </tr>
    </table>
    
    <table style="width: 100%; border: none;">
        <tr>
            <td style="text-align: right; vertical-align: top;">
            عدد ........ /م.م/2024
            </td>
        </tr>
    </table>

    <table style="width: 100%; border: none;">
         <tr>
        <td style="text-align: center; vertical-align: middle;">
        <p style="font-size: 23px; font-weight: 800;"><b>
            من رئيس كتابة النيابة العامة لدى المحكمة الابتدائية بفاس<br>
إلى<br>
السيد وزير العدل مديرية الموارد البشرية قسم الموظفين<br>
' . $masla7a . ' - الرباط<br>
تحت إشراف<br>
السيد وكيل الملك لدى المحكمة الابتدائية بفاس<br>
        </b></p>
        </td>
        
        </tr>
    </table>

    <table style="width: 100%; border: none; padding-bottom: 20px;">
    <tr>
      <td style="text-align: center;">
        <span><b><u> ورقة الإرسال </b></u></span>
      </td>
    </tr>
      </table>

    <table border="1px" style="width: 100%;">
        <tr>
            <td style="text-align: center; width: 45%; min-height: fit-content; background-color: lightgray;">
                <span><b>ملاحظات</b></span>
            </td>
            <td style="text-align: center; width: 10%; background-color: lightgray;">
                <span><b>عدد المرفقات</b></span>
            </td>
            <td style="text-align: center; width: 45%; background-color: lightgray;">
                <span><b> نوع المراسلات وتلخيص موضوعها</b></span>
            </td>
        </tr>

        <tr style="border: solid 1px;">
            <td style="text-align: right;">
                <p style="font-size: 23px; font-weight: 800;">
                    أحيل عليكم المشار إليه يمنته لكل غاية
مفيدة.<br><br>
        
وتفضلوا بقبول فائق عبارات التقدير
والاحترام.<br><br>
        
<b style="text-align:center"> والسلام <br></b><br>
<b style="text-align:center">رئيس كتابة النيابة العامة<br></b><br><br><br>
                </p>
                </td>

                <td>
                    <br><br><p>
                    <b>0' . $morfa9at . '</b></p>
                </td>
                <td style="text-align: right;">
                    <p style="font-size: 23px; font-weight: 800;"><b><u> تجدون رفقته : </u></b><br>
                    - إعلام باستئناف العمل بعد العودة من ' . $type_cont_name . ' مدتها (' . $duree . ' أيام ' . $al3amal . ') 
                    عن سنة ' . $year . ' تتعلق بالسيد : <br>
                      ' . $nom . ' ' . $prenom . ', ' . $trav . '. <br>
                    ' . $id_p . '<u><b> رقمه المالي : </b></u> <br><br><br> ' . $remarque . '. 
                    </p>
                </td>

        </tr>
            
    </table>
  </div>
</body>
</html>
';

// Clear the output buffer
ob_end_clean();

// Convert HTML to PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output($nom . '_' . $prenom . '.pdf', 'I');
?>
