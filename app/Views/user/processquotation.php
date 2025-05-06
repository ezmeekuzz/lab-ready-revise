<?=$this->include('user/header');?>
<style>
#cadItems td {
    vertical-align: middle; /* Vertically center contents */
    height: 200px; /* Or any size you want */
    position: relative; /* Needed if you later want absolute children */
}
.stl-viewer-container {
    width: 100%;
    height: 100%;
}
</style>
<div class="app-container" id="uploadArea">
    <?=$this->include('user/sidebar');?>
    <div class="app-main" id="main">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h4><i class="fa fa-file-pdf-o"></i> Request New Quote</h4>
                        <nav>
                            <ol class="breadcrumb p-0 m-0">
                                <li class="breadcrumb-item"><a href="/"><i class="ti ti-home"></i></a></li>
                                <li class="breadcrumb-item active">Request New Quote</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Drag & Drop Upload Section -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-4">
                            <h5 class="text-muted" id="dropFilesLabel">Drop Your Files</h5>
                            <form id="requestquotation">
                                <div class="form-group">
                                    <div class="upload-area">
                                        <i class="fa fa-upload fa-2x text-muted"></i>
                                        <p class="text-muted">Drag & Drop CAD models (<b>STEP or STL only</b>)</p>
                                        <button type="button" id="fileSelectBtn">Select Files</button>
                                        <input type="file" id="fileInput" name="files" multiple hidden accept=".step,.iges,.igs,.pdf,.STEP,.IGES,.IGS,.PDF">
                                        <div id="fileList"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quotation Table -->
            <div class="row justify-content-center mt-4">
                <div class="col-lg-10">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <table id="cadItems" class="table table-bordered text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%">CAD Model</th>
                                        <th style="width: 30%">Model File Name</th>
                                        <th style="width: 30%">Print File</th>
                                        <th style="width: 10%"></th>
                                    </tr>
                                </thead>
                            </table>
                            <form id="submitQuotation" class="mt-5">
                                <h5>Fill in the sections below in a casual manner as you might in a text or email</h5>

                                <!-- Section 1: Material and Surface Finish Details -->
                                <div class="text-start mt-4" id="material-finish-section">
                                    <label>Enter Material and Surface Finish Details</label>
                                    <textarea class="form-control" rows="3" placeholder="Only use this section to provide information that is not on the prints (no need to provide information twice). 
                            Examples: Laser marking needed? Assembly needed?" id="materialFinishTextarea" style="min-height: 150px; resize: none; width: 100%;"></textarea>
                                </div>

                                <!-- Section 2: Quantity(s) to Quote -->
                                <div class="text-start mt-4" id="quantity-section">
                                    <label>Enter Quantity(s) to Quote</label>
                                    <textarea class="form-control" rows="3" placeholder="Example: Quote 5, 10, and 25 pieces." id="quantityTextarea" style="min-height: 150px; resize: none; width: 100%;"></textarea>
                                </div>

                                <!-- Section 3: Other Relevant Details -->
                                <div class="text-start mt-4" id="other-details-section">
                                    <label>Enter any Other Details You Deem Relevant</label>
                                    <textarea class="form-control" rows="3" id="otherDetailsTextarea" placeholder="Example: Delivery requirements, inspection requirements, certifications, special handling instructions, etc." style="min-height: 150px; resize: none; width: 100%;"></textarea>
                                </div>

                                <!-- Submit Button -->
                                <div class="row mt-4">
                                    <div class="col-lg-12 text-center">
                                        <button type="submit" class="btn btn-lg btn-primary px-4 py-2">
                                            CLICK WHEN YOU’RE READY TO SUBMIT FOR QUOTING
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?=$this->include('user/footer');?>
<script src="<?=base_url();?>assets/stl_viewer/stl_viewer.min.js"></script>
<script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
<script src="<?=base_url();?>assets/js/processquotation.js"></script>
