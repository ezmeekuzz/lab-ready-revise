$(document).ready(function () {

    let table = $('#requestquotationmasterlist').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "/requestquotationlist/getData",
            "type": "POST",
            "data": function (d) {
                d.year = $('#yearFilter').val();   // Pass the selected year
                d.month = $('#monthFilter').val(); // Pass the selected month
            }
        },
        "columns": [
            { "data": "reference_number" },
            { "data": "quotation_name" },
            {
                "data": "status",
                "render": function (data, type, row) {
                    let statusClass = '';
                    let stat = '';
                    let link = '';

                    if (data === 'Pending') {
                        statusClass = 'badge-warning p-1 rounded';
                        stat = "Ongoing";
                    } else if (data === 'Submitted') {
                        statusClass = 'badge-success p-1 rounded';
                        stat = "Pending";
                    } else if (data === 'Shipped') {
                        statusClass = 'badge-success p-1 rounded';
                        stat = "Shipped (Track Order)";
                        link = `<a href="/requestquotationlist/shipmentLink/${row.quotation_id}" target="_blank" style="color: inherit; text-decoration: none;">${stat}</a>`;
                    } else {
                        statusClass = 'badge-info p-1 rounded';
                        stat = "Quote has arrived. See quote page";
                    }
                    return link !== '' ? `<span class="${statusClass}">${link}</span>` : `<span class="${statusClass}">${stat}</span>`;
                }
            },
            {
                "data": "created_at",
                "render": function (data, type, row) {
                    if (!data) return "";
                    let date = new Date(data);
                    return new Intl.DateTimeFormat('en-US', {
                        month: '2-digit', 
                        day: '2-digit', 
                        year: 'numeric',
                        hour: '2-digit', 
                        minute: '2-digit',
                        hour12: true
                    }).format(date);
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function (data, type, row) {
                    return `
                        <a href="#" title="View or Edit (If Pending) Quote" class="quotation-list" data-id="${row.quotation_id}" data-status="${row.status}" style="color: orange;">
                            <i class="fa fa-file-text" style="font-size: 18px;"></i>
                        </a>
                        <a href="#" title="Duplicate Quotation" class="duplicate-quotation" data-id="${row.quotation_id}" data-status="${row.status}" style="color: blue;">
                            <i class="fa fa-copy" style="font-size: 18px;"></i>
                        </a>
                        <a href="/requestquotationlist/downloadAllFiles/${row.quotation_id}" download title="Download Files" style="color: green;">
                            <i class="ti ti-download" style="font-size: 18px;"></i>
                        </a>
                        <a href="#" title="Delete" class="delete-request" data-id="${row.quotation_id}" style="color: red;">
                            <i class="ti ti-trash" style="font-size: 18px;"></i>
                        </a>`;
                }
            }
        ],
        "createdRow": function (row, data, dataIndex) {
            $(row).attr('data-id', data.quotation_id);
        },
        "initComplete": function (settings, json) {
            $(this).trigger('dt-init-complete');
        }
    });

    // Handle View/Edit Click
    $(document).on('click', '.quotation-list', function (e) {
        e.preventDefault();
        let status = $(this).data('status');
        let id = $(this).data('id');

        if (status === 'Done') {
            Swal.fire({
                icon: 'warning',
                title: 'Action Not Allowed',
                text: 'You can only edit quotations with a Ongoing/Pending status.',
            });
        } else {
            window.location.href = `/process-quotation/${id}`;
        }
    });

    // Apply filter when the filter button is clicked
    $('#filterBtn').on('click', function() {
        table.ajax.reload(); // Reload DataTable with new filters
    });

    // Reset filter button
    $('#resetBtn').on('click', function() {
        $('#yearFilter').val('');   // Reset year filter
        $('#monthFilter').val('');  // Reset month filter
        table.ajax.reload();        // Reload DataTable with reset filters
    });
    $(document).on('click', '.delete-request', function (e) {
        e.preventDefault();
        let id = $(this).data('id');
    
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/requestquotationlist/deleteQuotation/${id}`,
                    type: 'POST',
                    data: { id: id },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            $('#requestquotationmasterlist').DataTable().ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error!', 'Something went wrong. Try again later.', 'error');
                    }
                });
            }
        });
    });
    $(document).on('click', '.duplicate-quotation', function (e) {
        e.preventDefault();
        let id = $(this).data('id');
    
        // Show Swal input field for new quotation name
        Swal.fire({
            title: 'Duplicate Quotation',
            text: 'Enter a new name for the duplicated quotation:',
            input: 'text',
            inputPlaceholder: 'Quotation Name',
            showCancelButton: true,
            confirmButtonText: 'Duplicate',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Quotation name is required!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let newQuotationName = result.value;
    
                // Send AJAX request to duplicate the quotation
                $.ajax({
                    url: `/requestquotationlist/duplicateQuotation/${id}`,
                    type: 'POST',
                    data: { id: id, new_name: newQuotationName },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Duplicated!', response.message, 'success');
                            $('#requestquotationmasterlist').DataTable().ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error!', 'Something went wrong. Try again later.', 'error');
                    }
                });
            }
        });
    });    
});
