document.addEventListener("DOMContentLoaded", function () {
    let uploadArea = document.getElementById("uploadArea");
    let fileInput = document.getElementById("fileInput");
    // Only allow STEP and STL files (case insensitive)
    let validExtensions = [".step", ".stp", ".stl"];
    let dropFilesLabel = document.getElementById("dropFilesLabel");
    let fileSelectBtn = document.getElementById("fileSelectBtn");

    if (fileSelectBtn) {
        fileSelectBtn.addEventListener("click", function () {
            fileInput.click(); // Triggers the hidden file input
        });
    }

    let table = $('#cadItems').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "/processquotation/getData",
            "type": "GET",
            "dataSrc": function (json) {
                // Update label based on whether we have files
                updateDropFilesLabel(json.data && json.data.length > 0);
                return json.data;
            }
        },
        "columns": [
            {
                "data": "stl_file_location",
                "render": function (data, type, row) {
                    if (data.toLowerCase().endsWith(".stl")) {
                        return `
                            <div class="stl-wrapper" style="width: 100%; height: 100%; display: flex; justify-content: center; align-items: center;">
                                <div class="stl-viewer-container" id="stl-viewer-${row.item_id}" data-stl="/${data}" style="width: 80%; height: 100%;"></div>
                            </div>
                        `;
                    } else {
                        return `<a href="${data}" target="_blank">${row.cad_file_name}</a>`;
                    }
                }
            },
            { "data": "cad_file_name" },
            {
                "data": null,
                "render": function (data, type, row) {
                    return `
                        <div class="file-upload-wrapper text-center">
                            <label class="file-label btn btn-sm btn-outline-primary" for="file-${row.item_id}" style="cursor: pointer;">
                                <i class="fa fa-upload"></i> ${row.print_file_name || "Upload Print File"}
                            </label>
                            <small class="d-block text-muted mt-1">Click to upload a print file</small>
                            <input type="file" class="file-upload form-control" id="file-${row.item_id}" data-id="${row.item_id}" style="display: none;" />
                        </div>
                    `;
                }
            },            
            {
                "data": null,
                "render": function (data, type, row) {
                    return `<a href="#" class="delete-item" data-id="${row.item_id}" style="color: red; font-size: 20px;">
                                <i class="fa fa-trash"></i>
                            </a>`;
                }
            }
        ],
        "createdRow": function (row, data, dataIndex) {
            $(row).attr('data-id', data.item_id);
            $('#materialFinishTextarea').val(data.other_information);
            $('#quantityTextarea').val(data.quantity_to_quote);
            $('#otherDetailsTextarea').val(data.relevant_details);
        },
        "initComplete": function (settings, json) {
            $(this).trigger('dt-init-complete');
            $('.stl-viewer-container').each(function () {
                let container = $(this);
                let stlLocation = container.data('stl');
        
                initializeStlViewer(container[0], stlLocation);
            });
            
            // Initial label update
            table.ajax.reload(null, false);
        }
    });

    // Function to update the drop files label
    function updateDropFilesLabel(hasFiles) {
        dropFilesLabel.textContent = hasFiles ? 'Drop Any Missing Files Here' : 'Drop Your Files';
    }

    $(document).on("change", ".file-upload", function () {
        let fileInput = $(this);
        let fileName = fileInput[0].files.length > 0 ? fileInput[0].files[0].name : "Choose File";
        fileInput.siblings(".file-label").text(fileName);
    });
    
    // Trigger file selection when clicking the label or button
    $(document).on("click", ".select-file-btn", function () {
        let itemId = $(this).data("id");
        $("#file-" + itemId).click();
    });
    
    // Prevent default behavior for drag events
    ["dragenter", "dragover", "dragleave", "drop"].forEach(event => {
        uploadArea.addEventListener(event, e => e.preventDefault());
    });

    // Highlight area when dragging over
    ["dragenter", "dragover"].forEach(event => {
        uploadArea.addEventListener(event, () => uploadArea.classList.add("drag-over"));
    });

    // Remove highlight on drag leave
    ["dragleave", "drop"].forEach(event => {
        uploadArea.addEventListener(event, () => uploadArea.classList.remove("drag-over"));
    });

    // Handle file drop
    uploadArea.addEventListener("drop", (e) => {
        let droppedFiles = Array.from(e.dataTransfer.files);
        handleFiles(droppedFiles);
    });

    // Handle file selection
    fileInput.addEventListener("change", (e) => {
        let selectedFiles = Array.from(e.target.files);
        handleFiles(selectedFiles);
    });

    function handleFiles(files) {
        let formData = new FormData();
        let validFiles = [];
        let invalidFiles = [];

        files.forEach(file => {
            let fileExt = file.name.slice(file.name.lastIndexOf(".")).toLowerCase();
            if (!validExtensions.includes(fileExt)) {
                invalidFiles.push(file.name);
            } else {
                formData.append("files[]", file);
                validFiles.push(file.name);
            }
        });

        // Show error if any invalid files are found
        if (invalidFiles.length > 0) {
            Swal.fire({
                icon: "error",
                title: "Invalid File Type",
                html: `The following files are not allowed: <strong>${invalidFiles.join(", ")}</strong>.<br><br>
                       Only <strong>STEP (.step/.stp)</strong> and <strong>STL (.stl)</strong> files are supported.`,
                confirmButtonColor: "#3085d6"
            });
            return; // Stop execution if invalid files exist
        }

        // Only upload if there are valid files
        if (validFiles.length > 0) {
            uploadFiles(formData, validFiles);
        }
    }

    function uploadFiles(formData, fileNames) {
        Swal.fire({
            title: "Uploading...",
            text: "Please wait while your files are being uploaded.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    
        fetch("/processquotation/uploadFiles", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                Swal.fire({
                    icon: "error",
                    title: "Upload Failed",
                    text: data.error
                });
                return;
            }
    
            let successMessage = "Uploaded: " + fileNames.join(", ");
    
            if (data.conversion_errors && data.conversion_errors.length > 0) {
                successMessage += `\n\n⚠️ Some files failed to convert:\n${data.conversion_errors.join("\n")}`;
            }
    
            Swal.fire({
                icon: "success",
                title: "Upload Successful",
                text: successMessage
            });
    
            table.ajax.reload(null, false);
    
            setTimeout(() => {
                $(".stl-viewer-container").each(function () {
                    let container = $(this);
                    let stlLocation = container.data("stl");
                    initializeStlViewer(container[0], stlLocation);
                });
            }, 1000);
        })
        .catch(error => {
            Swal.fire({
                icon: "error",
                title: "Upload Failed",
                text: "Something went wrong. Please try again."
            });
            console.error("Upload Error:", error);
        });
    }
    
    function initializeStlViewer(stlContainer, stlLocation) {
        new StlViewer(stlContainer, {
            models: [{
                filename: stlLocation
            }],
            canvasConfig: {
                antialias: true,
                quality: 'high'
            },
            solid: true,
        });
    }
    
    $(document).on("change", ".file-upload", function () {
        const fileInput = $(this);
        const file = fileInput[0].files[0];
        const itemId = fileInput.data("id");
    
        if (file) {
            const fileExt = file.name.slice(file.name.lastIndexOf(".")).toLowerCase();
            
            if (fileExt !== ".pdf") {
                Swal.fire({
                    icon: "error",
                    title: "Invalid File Type",
                    text: "Only PDF files are allowed for print file uploads."
                });
                fileInput.val("");
                return;
            }
    
            const formData = new FormData();
            formData.append("file", file);
            formData.append("item_id", itemId);
    
            Swal.fire({ 
                title: "Uploading...", 
                text: "Please wait.", 
                allowOutsideClick: false, 
                didOpen: () => Swal.showLoading() 
            });
    
            fetch("/processquotation/uploadSingleFile", { method: "POST", body: formData })
                .then(response => response.json())
                .then(() => {
                    Swal.fire({ 
                        icon: "success", 
                        title: "Upload Successful", 
                        text: "File uploaded successfully." 
                    });
                    fileInput.siblings(".file-label").html(`<i class="fa fa-upload"></i> ${file.name}`);
                })
                .catch(error => {
                    Swal.fire({ 
                        icon: "error", 
                        title: "Upload Failed", 
                        text: "Something went wrong. Please try again." 
                    });
                    console.error("Upload Error:", error);
                });
        }
    });
    
    $(document).on("click", ".delete-item", function (e) {
        e.preventDefault();
        let itemId = $(this).data("id");
    
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/processquotation/deleteItem",
                    type: "POST",
                    data: { item_id: itemId },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire("Deleted!", "Your item has been deleted.", "success");
                            table.ajax.reload(null, false);

                            setTimeout(() => {
                                $(".stl-viewer-container").each(function () {
                                    let container = $(this);
                                    let stlLocation = container.data("stl");
                                    initializeStlViewer(container[0], stlLocation);
                                });
                            }, 1000);
                        } else {
                            Swal.fire("Error!", response.message || "Unable to delete item.", "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error!", "Something went wrong. Please try again.", "error");
                    }
                });
            }
        });
    });    
});

$(document).ready(function () {
    $("#submitQuotation").submit(function (e) {
        e.preventDefault();

        let formData = {
            material_finish_details: $("#material-finish-section textarea").val().trim(),
            quantity_to_quote: $("#quantity-section textarea").val().trim(),
            other_relevant_details: $("#other-details-section textarea").val().trim()
        };

        Swal.fire({
            title: "Submitting...",
            text: "Please wait while we process your request.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "/processquotation/submitQuotation",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Submitted!",
                        text: response.success,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href="/request-quotation-list";
                    });
                    $("#submitQuotation")[0].reset();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: response.error
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "Something went wrong!"
                });
            }
        });
    });
});