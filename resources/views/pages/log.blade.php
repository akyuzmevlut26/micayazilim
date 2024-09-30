@extends('layouts.layout')

@section('content')
    <div class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <a href="/" class="btn btn-danger btn-lg active" role="button" aria-pressed="true">Ürünler</a>
                </div>
            </div>
            <h2 class="my-4">Log Kayıtları</h2>
            <div class="row flex-xl-nowrap">
                <div id="logContainer" class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Tür</th>
                            <th scope="col">İlişki Türü</th>
                            <th scope="col">Açıklama</th>
                            <th scope="col">Tarih</th>
                        </tr>
                        </thead>

                        <tbody></tbody>
                    </table>

                    @include('layouts.pagination')
                </div>
            </div>
        </div>
    </div>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(function () {
            loadLogs();

            setInterval(() => {
                loadLogs();
            }, 5000);
        });

        function loadLogs() {
            let container = $('#logContainer');
            let content = container.find('table tbody');

            $.get(sprintf('/api/log%s', [((window.location.search.trim().length) ? (window.location.search + '&sort=id:desc') : '?sort=id:desc')]), (response) => {
                content.html('');

                if (response.data) {
                    response.data.forEach((log, index) => {
                        content.append(
                            $('<tr>').append(
                                $('<th>').attr('scope', 'row').html(log.id),
                                $('<td>').html(log.type),
                                $('<td>').html(log.relation_type),
                                $('<td>').html(log.description),
                                $('<td>').html(log.created_at_text),
                            )
                        );
                    });
                }

                initPagination(response.meta);
            });
        }
    </script>
@endsection
