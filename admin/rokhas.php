<?php
include_once('../include/dbConn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type_contrat = $_POST['Type_Contrat'];
    $duree_contrat = $_POST['Duree_Contrat'];
    $duree_validite = $_POST['Duree_Validité'];
    $rasmiya = isset($_POST['rasmiya']) ? $_POST['rasmiya'] : 0; // Default to 0 if not set
    $supp = 0;

    // Check if the contract name already exists
    $sql = "SELECT COUNT(*) AS count FROM contrat WHERE Type_Contrat = :type_contrat";
    $query = $dbh->prepare($sql);
    $query->bindParam(':type_contrat', $type_contrat, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0 && empty($_POST['ID_Contrat'])) {
        $message = "خطأ: يوجد عقد بهذا الاسم بالفعل.";
    } else {
        if (isset($_POST['ID_Contrat']) && !empty($_POST['ID_Contrat'])) {
            $id_contrat = $_POST['ID_Contrat'];
            $sql = "UPDATE contrat SET Type_Contrat = :type_contrat, Duree_Contrat = :duree_contrat, Duree_Validité = :duree_validite, Rasmiya = :rasmiya WHERE ID_Contrat = :id_contrat";
            $query = $dbh->prepare($sql);
            $query->bindParam(':id_contrat', $id_contrat, PDO::PARAM_INT);
        } else {
            $sql = "INSERT INTO contrat (Type_Contrat, Duree_Contrat, Duree_Validité, Rasmiya, Supp) VALUES (:type_contrat, :duree_contrat, :duree_validite, :rasmiya, :supp)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':supp', $supp, PDO::PARAM_INT);
        }

        $query->bindParam(':type_contrat', $type_contrat, PDO::PARAM_STR);
        $query->bindParam(':duree_contrat', $duree_contrat, PDO::PARAM_INT);
        $query->bindParam(':duree_validite', $duree_validite, PDO::PARAM_INT);
        $query->bindParam(':rasmiya', $rasmiya, PDO::PARAM_INT);

        if ($query->execute()) {
            $message = isset($id_contrat) ? "تم تحديث السجل بنجاح" : "تم إنشاء سجل جديد بنجاح";
        } else {
            $message = "خطأ: " . $query->errorInfo()[2];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <title>برنامج تنظيم العطل</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../images/M6.png">
    <script defer src="../assets/plugins/fontawesome/js/all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/portal.css">
    <style>
        .edit-icon {
            width: 1em;
            height: 1em;
        }
        .app-wrapper::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../images/zelij.jpg') repeat;
            background-size: cover;
            opacity: 0.2;
            z-index: -1;
        }
    </style>
</head>
<body class="app">
    <?php include_once('headerA.php');?>

    <div class="app-wrapper">
        <div class="app-content pt-3 p-md-3 p-lg-4">
            <div class="container-xl">
                <div class="row g-3 mb-4 align-items-center justify-content-between">
                    <div class="col-auto">
                        <h1 class="app-page-title mb-0">الطلبات</h1>
                    </div>

                    <div class="col-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCenter">إضافة رخصة</button>
                    </div>
                    <hr class="mb-4">
                </div>

                <!-- Vertically Centered Modal -->
                <div class="modal fade" id="modalCenter" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalCenterTitle">رخصة جديدة</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addContractForm" action="" method="post">
                                    <input type="hidden" id="editContractId" name="ID_Contrat">
                                    <div class="row">
                                        <div class="col mb-3">
                                            <label for="TypeRokhsa" class="form-label">نوع الرخصة</label>
                                            <input type="text" id="TypeRokhsa" name="Type_Contrat" class="form-control" placeholder="Enter Name" required />
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col mb-0">
                                            <label for="duree" class="form-label">مدتها</label>
                                            <input type="number" id="duree" name="Duree_Contrat" class="form-control" placeholder="1234" required />
                                        </div>
                                        <div class="col mb-0">
                                            <label for="DureeVal" class="form-label">مدة صلاحيتها</label>
                                            <input type="number" id="DureeVal" name="Duree_Validité" class="form-control" placeholder="1234" required />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col mb-3">
                                            <label class="form-label">نوعها<span class="ms-2" data-bs-container="body" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-content=". النسخ الرسمية لا يتم احتساب ايام نهاية الأسبوع والعطل">
                                                <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-info-circle" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                    <path d="M8.93 6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469ل .738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598ل .088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588z"/>
                                                    <circle cx="8" cy="4.5" r="1"/>
                                                </svg></span>
                                            </label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="rasmiya" name="rasmiya" value="1">
                                                <label class="form-check-label" for="rasmiya">رسمية</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="ghayr_rasmiya" name="rasmiya" value="0">
                                                <label class="form-check-label" for="ghayr_rasmiya">غير رسمية</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                        <button type="submit" class="btn btn-primary">إضافة</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="orders-table-tab-content">
                    <div class="tab-pane fade show active" id="orders-all" role="tabpanel" aria-labelledby="orders-all-tab">
                        <div class="app-card app-card-orders-table shadow-sm mb-5">
                            <div class="app-card-body">
                                <div class="table-responsive">
                                    <table class="table app-table-hover mb-0 text-left">
                                        <thead>
                                            <tr>
                                                <th class="cell">رقم الرخصة</th>
                                                <th class="cell">نوع الرخصة</th>
                                                <th class="cell">مدة صلاحيتها</th>
                                                <th class="cell">مدة العطلة</th>
                                                <th class="cell">رسمية</th>
                                                <th class="cell">تعديل</th>
                                                <th class="cell">تحديث الرصيد</th>
                                                <th class="cell">حذف</th>
                                            </tr>
                                        </thead>
                                        <tbody id="contracts-table-body">
                                        <?php
                                        include_once('../include/dbConn.php');

                                        $sql = "SELECT * FROM contrat WHERE Supp = 0";
                                        $query = $dbh->query($sql);
                                        while ($contract = $query->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<tr>";
                                            echo "<td class='cell'>" . htmlspecialchars($contract['ID_Contrat']) . "</td>";
                                            echo "<td class='cell'>" . htmlspecialchars($contract['Type_Contrat']) . "</td>";
                                            echo "<td class='cell'>" . htmlspecialchars($contract['Duree_Contrat']) . "</td>";
                                            echo "<td class='cell'>" . htmlspecialchars($contract['Duree_Validité']) . "</td>";
                                            echo "<td class='cell'>" . ($contract['RASMIYA'] == 1 ? "نعم" : "لا") . "</td>";
                                            echo "<td class='cell'><a href='#' class='btn-sm edit-button' data-contract-id='" . htmlspecialchars($contract['ID_Contrat']) . "'>
                                                <svg class='edit-icon' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'>
                                                    <path fill='currentColor' d='M21 12a1 1 0 0 0-1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h6a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1m-15 .76V17a1 1 0 0 0 1 1h4.24a1 1 0 0 0 .71-.29l6.92-6.93L21.71 8a1 1 0 0 0 0-1.42l-4.24-4.29a1 1 0 0 0-1.42 0l-2.82 2.83l-6.94 6.93a1 1 0 0 0-.29.71m10.76-8.35ل 2.83 2.83l-1.42 1.42ل-2.83-2.83ZM8 13.17l5.93-5.93ل 2.83 2.83L10.83 16H8Z'/>
                                                </svg>
                                            </a></td>";

                                            // Show update button only for Rasmiya contracts
                                            if ($contract['RASMIYA'] == 1) {
                                                echo "<td class='cell'>
                                                    <a href='#' class='btn-sm update-duration' data-contract-id='" . htmlspecialchars($contract['ID_Contrat']) . "'>
                                                        <svg class='edit-icon' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'>
                                                            <path fill='currentColor' d='M12 2a1 1 0 0 0-1 1v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2h-6V3a1 1 0 0 0-1-1z'/>
                                                        </svg>
                                                    </a>
                                                </td>";
                                            } else {
                                                echo "<td class='cell'>---</td>";
                                            }

                                            echo "<td class='cell'><a class='btn-sm app-btn-secondary custom-hover-red' href='../include/handleAction.php?action=delete&ID_Contrat=" . urlencode($contract['ID_Contrat']) . "' onclick='return confirm(\"هل انت متاكد من انك تريد الحذف ؟\")'>حذف</a></td>";
                                            echo "</tr>";
                                        }
                                        ?>

                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const updateButtons = document.querySelectorAll('.update-duration');
        updateButtons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const contractId = this.dataset.contractId;
                if (confirm("هل أنت متأكد من أنك تريد تحديث مدة الرخصة لجميع المستخدمين؟")) {
                    fetch(`../include/update_users_rest.php?ID_Contrat=${contractId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('تم تحديث مدة الرخصة لجميع المستخدمين بنجاح.');
                                location.reload();
                            } else {
                                alert('خطأ: ' + data.error);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        });
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.edit-button');
        const modal = new bootstrap.Modal(document.getElementById('modalCenter'));
        const form = document.getElementById('addContractForm');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const contractId = this.dataset.contractId;
                
                fetch(`../include/get_contract.php?ID_Contrat=${contractId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('editContractId').value = data.ID_Contrat;
                        document.getElementById('TypeRokhsa').value = data.Type_Contrat;
                        document.getElementById('DureeVal').value = data.Duree_Validité;
                        document.getElementById('duree').value = data.Duree_Contrat;
                        
                        if (data.RASMIYA == 1) {
                            document.getElementById('rasmiya').checked = true;
                            document.getElementById('ghayr_rasmiya').checked = false;
                        } else {
                            document.getElementById('rasmiya').checked = false;
                            document.getElementById('ghayr_rasmiya').checked = true;
                        }

                        modal.show();
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    });

    document.getElementById('rasmiya').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('ghayr_rasmiya').checked = false;
        }
    });

    document.getElementById('ghayr_rasmiya').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('rasmiya').checked = false;
        }
    });
    </script>

</body>
</html>
