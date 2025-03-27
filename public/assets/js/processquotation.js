document.addEventListener("DOMContentLoaded", function () {
    let uploadArea = document.getElementById("uploadArea");
    let fileInput = document.getElementById("fileInput");
    let validExtensions = [".step", ".iges", ".igs", ".stl", ".pdf", ".STEP", ".IGES", ".IGS", ".STL", ".PDF"];
    
    let table = $('#cadItems').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "/processquotation/getData",
            "type": "GET"
        },
        "columns": [
            {
                "data": "stl_file_location",
                "render": function (data, type, row) {
                    if (data.toLowerCase().endsWith(".stl")) {
                        return `
                            <div class="stl-viewer-container" id="stl-viewer-${row.item_id}" data-stl="/${data}" style="width: 150px; height: 150px;"></div>
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
                        <div class="file-upload-wrapper">
                            <label class="file-label" for="file-${row.item_id}">${row.print_file_name || "Choose File"}</label>
                            <input type="file" class="file-upload form-control" id="file-${row.item_id}" data-id="${row.item_id}" style="display: none;" />
                        </div>`;
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
        },
        "initComplete": function (settings, json) {
            $(this).trigger('dt-init-complete');
            $('.stl-viewer-container').each(function () {
                let container = $(this);
                let stlLocation = container.data('stl');
        
                initializeStlViewer(container[0], stlLocation);
            });
        }
    });

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

        files.forEach(file => {
            let fileExt = file.name.slice(file.name.lastIndexOf("."));
            if (!validExtensions.includes(fileExt)) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid File Type",
                    text: "Only STEP, IGES, STL, and PDF files are allowed."
                });
            } else {
                formData.append("files[]", file);
                validFiles.push(file.name);
            }
        });

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
                // If there's an error, show an error message
                Swal.fire({
                    icon: "error",
                    title: "Upload Failed",
                    text: data.error
                });
                return;
            }
    
            let successMessage = "Uploaded: " + fileNames.join(", ");
    
            // Handle conversion errors
            if (data.conversion_errors && data.conversion_errors.length > 0) {
                successMessage += `\n\n⚠️ Some files failed to convert:\n${data.conversion_errors.join("\n")}`;
            }
    
            // Show success message with details
            Swal.fire({
                icon: "success",
                title: "Upload Successful",
                text: successMessage
            });
    
            // Reload the DataTable without resetting pagination
            table.ajax.reload(null, false);
    
            // Refresh STL Viewer
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
        // Initialize StlViewer with the provided container and STL file location
        new StlViewer(stlContainer, {
            // Provide the STL file location
            models: [{
                filename: stlLocation
            }],
            // Configure canvas settings
            canvasConfig: {
                antialias: true, // Enable antialiasing for smoother edges
                quality: 'high' // Set rendering quality to high
            },
            // Render the model as solid
            solid: true,
            // Enable rotation of the model
            rotate: true,
            // Automatically resize the viewer based on container size
            autoResize: true,
            // Add light sources for better visibility
            lights: [
                { dir: [1, 1, 1], color: [1, 1, 1] }, // White light from one direction
                { dir: [-1, -1, -1], color: [0.5, 0.5, 0.5] } // Dim light from the opposite direction
            ],
            // Set initial pan position
            pan: [0, 0] // Center the model initially
        });
    }
    
    $(document).on("change", ".file-upload", function () {
        const fileInput = $(this);
        const file = fileInput[0].files[0];
        const itemId = fileInput.data("id");
        
        if (file) {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("item_id", itemId);
            
            Swal.fire({ title: "Uploading...", text: "Please wait.", allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            fetch("/processquotation/uploadSingleFile", { method: "POST", body: formData })
                .then(response => response.json())
                .then(() => {
                    Swal.fire({ icon: "success", title: "Upload Successful", text: "File uploaded successfully." });
                    fileInput.siblings(".file-label").text(file.name);
                })
                .catch(error => {
                    Swal.fire({ icon: "error", title: "Upload Failed", text: "Something went wrong. Please try again." });
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
                            table.ajax.reload(null, false); // Reload DataTable without resetting pagination

                            setTimeout(() => {
                                $(".stl-viewer-container").each(function () {
                                    let container = $(this);
                                    let stlLocation = container.data("stl");
                    
                                    initializeStlViewer(container[0], stlLocation);
                                });
                            }, 1000); // Slight delay to ensure table reloads first
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
        e.preventDefault(); // Prevent default form submission

        let formData = {
            additional_info: $("textarea").val().trim()
        };

        // Show SweetAlert2 loading effect
        Swal.fire({
            title: "Submitting...",
            text: "Please wait while we process your request.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading(); // Display loading spinner
            }
        });

        $.ajax({
            url: "/processquotation/submitQuotation", // Adjust URL based on your route
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Submitted!",
                        text: response.success,
                        timer: 2000, // 2 seconds delay before reload
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href="/request-quotation-list";
                    });

                    // Optionally, reset the form
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
