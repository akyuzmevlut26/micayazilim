@extends('layouts.layout')

@section('content')
    <div class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <a href="/log" class="btn btn-danger btn-lg active" role="button" aria-pressed="true">Loglar</a>
                    <button class="btn btn-primary btn-lg" type="button" id="btn-product-refresh">Ürünleri Yenile</button>
                </div>
            </div>

            <h2 class="my-4">Ürünler</h2>

            <form class="row g-3 mb-4" method="GET">
                <div class="col-md-4">
                    <label for="filterTitle" class="form-label">Ürün Adı</label>
                    <input type="text" class="form-control" id="filterTitle" name="title" value="{{ $filterData['title'] ?? ''}}">
                </div>
                <div class="col-md-4">
                    <label for="filterBarcode" class="form-label">Barkod</label>
                    <input type="text" class="form-control" id="filterBarcode" name="barcode" value="{{ $filterData['barcode'] ?? ''}}">
                </div>
                <div class="col-md-4">
                    <label for="filterType" class="form-label">Ürün Tipi</label>
                    <select class="form-select" id="filterType" name="is_variant">
                        <option {{ (!isset($filterData['is_variant']) ? 'selected' : '') }} disabled value="">Seçim yapınız.</option>
                        <option value="0" {{ (isset($filterData['is_variant']) && $filterData['is_variant'] == 0 ? 'selected' : '') }}>Ürün</option>
                        <option value="1" {{ (isset($filterData['is_variant']) && $filterData['is_variant'] == 1 ? 'selected' : '') }}>Varyant</option>
                    </select>
                </div>
                <div class="col-12 text-center">
                    <a class="btn btn-danger" href="/">Alanları Sıfırla</a>
                    <button class="btn btn-primary" type="submit">Filtreyi Uygula</button>
                </div>
            </form>

            <div class="row flex-xl-nowrap">
                <div id="productContainer" class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Tip</th>
                            <th scope="col">Barkod</th>
                            <th scope="col">Başlık</th>
                            <th scope="col">Fiyat</th>
                            <th scope="col">Stok</th>
                            <th scope="col">İşlem</th>
                        </tr>
                        </thead>

                        <tbody></tbody>
                    </table>

                    @include('layouts.pagination')
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal modal-lg fade" id="productContentModal" tabindex="-1" role="dialog" aria-labelledby="productContentModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form id="productContentModalForm">
                <input type="hidden" name="id" value="">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productContentModalTitle">Ürün Düzenleme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="productPrice">Fiyat</label>
                            <input type="text" class="form-control" id="productPrice" name="sale_price" placeholder="Bir fiyat giriniz" required>
                        </div>

                        <div class="form-group mt-3">
                            <label for="productQuantity">Stok</label>
                            <input type="number" class="form-control" id="productQuantity" name="quantity" placeholder="Bir stok giriniz" min="1" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(function () {
            loadProducts();
        });

        function loadProducts() {
            let container = $('#productContainer');
            let content = container.find('table tbody');

            $.get(sprintf('/api/product%s', [((window.location.search.trim().length) ? (window.location.search + '&sort=id:desc&page_limit=50') : '?sort=id:desc&page_limit=50')]), (response) => {
                content.html('');

                if (response.data) {
                    response.data.forEach((product, index) => {
                        content.append(
                            $('<tr>').append(
                                $('<th>').attr('scope', 'row').html(product.id),
                                $('<td>').html(product.is_variant ? 'Varyant' : 'Ürün'),
                                $('<td>').html(product.barcode),
                                $('<td>').html(product.title),
                                $('<td>').html(product.sale_price),
                                $('<td>').html(product.quantity),
                                $('<td>').append(
                                    $('<button>').attr('type', 'button').addClass('btn btn-sm btn-primary m-1 btn-product-modal').data('product-id', product.id).text('Düzenle'),
                                )
                            )
                        );
                    });
                }

                initPagination(response.meta);
            });
        }

        $(document).on('click', '.btn-product-modal', function (e) {
            e.preventDefault();

            let btn = $(this);
            let productId = btn.data('product-id');

            const myModal = new bootstrap.Modal('#productContentModal')
            let productContentModalForm = $('#productContentModal').find('form');
            productContentModalForm[0].reset();

            if (productId) {
                $.get('{{ url('/api/product/') }}/' + productId, function (response) {
                    if (response.status) {
                        productContentModalForm.find('input[name="id"]').val(response.data.id);
                        productContentModalForm.find('input[name="sale_price"]').val(response.data.sale_price);
                        productContentModalForm.find('input[name="quantity"]').val(response.data.quantity);

                        myModal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata!',
                            text: 'İşleminiz gerçekleştirilirken beklenmedik bir hata oluştu.'
                        });
                    }
                });
            }
        });

        $(document).on('submit', '#productContentModalForm', function (e) {
            e.preventDefault();
            let form = $(this);
            let productId = form.find('input[name="id"]').val();
            let formData = form.serializeArray();

            if (productId) {
                formData.push({name: '_method', value: 'put'});

                $.post('{{ url('/api/product') }}/' + productId, formData, function (response) {
                    if (response.status) {
                        form[0].reset();
                        $('#productContentModal').modal('hide');
                        loadProducts();

                        Swal.fire({
                            icon: 'success',
                            title: 'İşlem Başarılı!',
                            text: (productId ? 'İçerik başarıyla güncellendi.' : 'İçerik başarıyla eklendi.')
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata!',
                            text: 'İşleminiz gerçekleştirilirken beklenmedik bir hata oluştu.'
                        });
                    }
                });
            }
        });


        $(document).on('click', '#btn-product-refresh', function (e) {
            e.preventDefault();

            let btn = $(this);
            let productId = btn.data('product-id');

            $.post('{{ url('/api/product/refresh') }}', function (response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'İşlem Başarılı!',
                        text: 'İşleminiz başarıyla gerçekleşti, ürünlerin yenilenmesi performans nedeniyle parça parça olacaktır.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'İşleminiz gerçekleştirilirken beklenmedik bir hata oluştu.'
                    });
                }
            });
        });
    </script>
@endsection
