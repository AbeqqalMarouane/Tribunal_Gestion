<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('dbConn.php'); // Ensure this file initializes $dbh

if (isset($_GET['ID_conge'])) {
    $ID_conge = $_GET['ID_conge'];
} else {
    die("خطأ: لم يتم العثور على معلمة 'ID_conge'.");
}

$sql = "SELECT Co.ID_User, C.Type_Contrat as Cont_type, Co.Duree_Conge as Duree, Co.Date_Debut, Co.Remarque 
        FROM conge Co 
        JOIN contrat c ON Co.ID_Contrat = c.ID_Contrat 
        WHERE Co.ID_Conge = :ID_conge";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':ID_conge', $ID_conge, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("خطأ: لم يتم العثور على بيانات للإيقاف المحدد.");
}

$type_cont = $row["Cont_type"];
$morfa9at = ($type_cont === "ادارية") ? 1 : 2;
$duree = $row["Duree"];
$date_start = $row["Date_Debut"];
$remarque = $row["Remarque"];

$id_p = $row["ID_User"];
$sql = "SELECT * FROM users WHERE ID_User = :ID_User";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':ID_User', $id_p, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("خطأ: لم يتم العثور على بيانات المستخدم للمعرف المحدد.");
}

$nom = $row["Nom"];
$prenom = $row["Prenom"];
$trav = $row["Metier"];
$Fin = $row["Nbr_Financier"];
$masla7a = ($row["Departement"]) ? "مصلحة كتاب الضبط والمحررين القضائيين" : "مصلحة المنتدبين القضائيين و الاطر التقنية";

// Include the TCPDF library
require_once('../tcpdf-main/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('STAGE');
$pdf->SetTitle('PDF Document');
$pdf->SetSubject('PDF Generation');
$pdf->SetKeywords('TCPDF, PDF, PHP');

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

// Define HTML content for the PDF
$html = '
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تصدير إلى PDF</title>
  <style>

    img {
      height: 5em;
      width: 5em; /* Corrected the width attribute */
    }
    td{
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
            <img style="height : 5em; width : 5em;" src="../images/logo.png" alt="صورة توضيحية" >
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
        <p style="font-size: 23px;font-weight: 800;"><b>
            من رئيس كتابة النيابة العامة لدى المحكمة الابتدائية بفاس<br>
إلى<br>
السيد وزير العدل مديرية الموارد البشرية قسم الموظفين<br>
'.$masla7a.' - الرباط<br>
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
            <td style="text-align: center; width: 45%; min-height: fit-content;  background-color: lightgray; ">
                <span><b>ملاحظات</b></span>
              
            </td>
            <td style="text-align: center; width: 10%;  background-color: lightgray;">
                <span><b>عدد المرفقات</b></span>
              
            </td>
            <td style="text-align: center; width: 45%;  background-color: lightgray;">
                <span><b> نوع المراسلات وتلخيص موضوعها</b></span>
              
            </td>
            
        </tr>

        <tr style="border: solid 1px;">
            <td style="text-align: right;">
                <p style="font-size: 22px;font-weight: 800;">
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
                    <b>0'.$morfa9at.'</b></p>
                </td>
                <td style="text-align: right;">
                    <p style="font-size: 23px;font-weight: 800;"><b><u> تجدون رفقته : </u></b><br>

                    -  إعلام بايقاف العمل لأجل رخصة<br>

                    '.$type_cont.'

                    مدتها ('.$duree.'

                     أيام '.$al3amal.') <br>
                     
                     
                     عن سنة  '.$year.'

                    تبتدئ من '.$date_start.'

                     تتعلق بالسيد : <br>

                      ' .$nom.' '.$prenom.', '.$trav.'. <br>

                    '.$Fin.'<u><b> رقمه المالي : </b></u> <br><br><br> '.$remarque.'
                    
                    
                    
                    
                    
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

// Write the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF to the browser with the user's name
$file_name = $nom . '_' . $prenom . '_ايقاف.pdf';

// Force the download of the PDF
$pdf->Output($file_name, 'D');

// Redirect to talabat.php after download
header("Location: ../admin/talabat.php");
exit();
?>
