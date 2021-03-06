jQuery(function ($) {
    var linkDatatable = $('meta[name=linkDatatable]').attr('content');

    $('#category').select2({
        placeholder: "---"
    });
    
    $('.datepicker').datepicker({
        autoclose: true
    });

    var _table = $("#datatable");
    var _btn_submit = $("#btn-submit");
    var _btn_reset = $("#btn-reset");

    var _datatable = _table.DataTable({
        processing: true,
        serverSide: true,
        lengthMenu: [[10, 25, 50, 100, 200,-1], [10, 25, 100, 200, "All"]],
        pageLength: 10,
        searching: false,
        ajax: {
            url: linkDatatable,
            data: function (d) {
                d.search = $('#search').val();
                d.category = $('#category').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            }
        },
        columns: [
            {data: 'id', name: 'id'},
            {data: 'category', name: 'category', orderable: false},
            {data: 'title', name: 'translations.title', orderable: false},
            {data: 'active', name: 'active', orderable: false},
            {data: 'published_date', name: 'published_date', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    _btn_submit.on('click', function (e) {
        _datatable.draw();
        e.preventDefault();

    });
    
    _btn_reset.on('click', function (e) {
        $("#search").val('');
        $("#start_date").val(null).trigger("change");
        $("#end_date").val(null).trigger("change");
        $('#category').val(null).trigger("change");
        setTimeout(function () {
            _datatable.draw();
        }, 1000);
        e.preventDefault();
    });
});