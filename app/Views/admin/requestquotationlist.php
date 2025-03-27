<!-- HTML Structure -->
<?=$this->include('admin/header');?>
<div class="app-container">
    <?=$this->include('admin/sidebar');?>
    <div class="app-main" id="main">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 m-b-30">
                    <div class="d-block d-sm-flex flex-nowrap align-items-center">
                        <div class="page-title mb-2 mb-sm-0">
                            <h4><i class="fa fa-archive"></i> Request Quotation List</h4>
                        </div>
                        <div class="ml-auto d-flex align-items-center">
                            <nav>
                                <ol class="breadcrumb p-0 m-b-0">
                                    <li class="breadcrumb-item">
                                        <a href="/"><i class="ti ti-home"></i></a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        Request Quotation List
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-lg-3">
                    <select id="yearFilter" class="form-control">
                        <option value="">Select Year</option>
                        <?php
                        $currentYear = date('Y');
                        for ($year = 2020; $year <= $currentYear; $year++) {
                            echo "<option value='$year'>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <select id="monthFilter" class="form-control">
                        <option value="">Select Month</option>
                        <?php
                        $months = array(
                            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                        );
                        foreach ($months as $num => $name) {
                            echo "<option value='$num'>$name</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-lg-6">
                    <button id="filterBtn" class="btn btn-primary">Filter</button>
                    <button id="resetBtn" class="btn btn-secondary">Reset</button>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-statistics">
                        <div class="card-header">
                            <div class="card-heading">
                                <h4 class="card-title"><i class="fa fa-archive"></i> Request Quotation List</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="datatable-wrapper table-responsive">
                                <table id="requestquotationmasterlist" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Full Name</th>
                                            <th>Email Address</th>
                                            <th>Reference</th>
                                            <th>Quotation Name</th>
                                            <th>Status</th>
                                            <th>Date Submitted</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="quotationModal" tabindex="-1" role="dialog" aria-labelledby="quotationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quotationModalLabel">Send Quotation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="sendquotation">
                    <div class="form-group">
                        <label for="nickname">Nickname</label>
                        <input type="text" name="nickname" id="nickname" class="form-control" placeholder="Enter Nickname" readonly>
                    </div>
                    <div class="form-group">
                        <label for="price">Quote Price</label>
                        <input type="text" name="price" id="price" class="form-control" placeholder="Enter Quote Price">
                    </div>
                    <div class="form-group">
                        <label for="invoicefile">Quote File</label>
                        <div class="custom-file">
                            <label class="custom-file-label" for="invoicefile">Choose file</label>
                            <input type="file" class="custom-file-input" id="invoicefile" name="invoicefile" accept="application/pdf">
                        </div>
                    </div>
                    <div class="form-group" hidden>
                        <label for="user_id">User ID</label>
                        <input type="text" name="user_id" id="user_id" class="form-control" placeholder="Enter User ID" readonly>
                    </div>
                    <div class="form-group" hidden>
                        <label for="quotation_id">Quotation ID</label>
                        <input type="text" name="quotation_id" id="quotation_id" class="form-control" placeholder="Enter User ID" readonly>
                    </div>
                    <button type="submit" class="btn btn-dark">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Quotation List Modal -->
<div class="modal fade" id="quotationListModal" tabindex="-1" role="dialog" aria-labelledby="quotationListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quotationListModalLabel">Quotation List</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-5" id="downloadAssemblyFiles">
                    <div class="col-lg-6">
                        <a href="#" class="btn bg-warning text-white mb-2" id="downloadAssembly"><i class="fa fa-download"></i> Download Assembly Print File</a>
                    </div>
                </div>
                <div id="quotationContainer" class="row"></div>
            </div>
        </div>
    </div>
</div>
<?=$this->include('admin/footer');?>
<script>
    baseURL = "<?=base_url();?>";
</script>
<script src="<?=base_url();?>assets/js/requestquotationlistAdmin.js"></script>
