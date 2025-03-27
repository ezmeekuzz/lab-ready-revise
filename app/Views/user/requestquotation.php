<?=$this->include('user/header');?>
<div class="app-container">
    <?=$this->include('user/sidebar');?>
    <div class="app-main" id="main">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 m-b-30">
                    <div class="d-block d-sm-flex flex-nowrap align-items-center">
                        <div class="page-title mb-2 mb-sm-0">
                            <h4><i class="fa fa-file-pdf-o"></i> Request New Quote</h4>
                        </div>
                        <div class="ml-auto d-flex align-items-center">
                            <nav>
                                <ol class="breadcrumb p-0 m-b-0">
                                    <li class="breadcrumb-item">
                                        <a href="/"><i class="ti ti-home"></i></a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        Request New Quote
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card card-statistics shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <h5 class="text-muted mb-4">Start by creating a new quotation request.</h5>
                            <button type="button" id="addQuotationBtn" class="btn btn-lg btn-primary px-4 py-2">
                                <i class="fa fa-plus"></i> Add Quotation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?=$this->include('user/footer');?>
<script src="<?=base_url();?>assets/stl_viewer/stl_viewer.min.js"></script>
<script>
    let baseURL = "<?=base_url();?>";
</script>
<script src="<?=base_url();?>assets/js/requestquotation.js"></script>