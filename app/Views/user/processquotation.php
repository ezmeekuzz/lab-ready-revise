<?=$this->include('user/header');?>
<div class="app-container">
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
                                    <div class="upload-area" id="uploadArea">
                                        <i class="fa fa-upload fa-2x text-muted"></i>
                                        <p class="text-muted">Drag & Drop All Files (<b>STEP, IGES, STL and PDF Only</b>)</p>
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
                                        <th>CAD Model</th>
                                        <th>Model File Name</th>
                                        <th>Print File</th>
                                        <th></th>
                                    </tr>
                                </thead>
                            </table>
                            <form id="submitQuotation">
                                <div class="text-start mt-4" id="additional-info-section">
                                    <label>Enter Material and Surface Finish Details</label>
                                    <textarea class="form-control" rows="3" placeholder="Enter Here" style="min-height: 150px; resize: none;"></textarea>
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
<script src="<?=base_url();?>assets/js/processquotation.js"></script>
