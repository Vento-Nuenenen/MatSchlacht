<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @yield('assets-css')
    <style>
        .sortable-header {
            cursor: pointer;
            position: relative;
            padding-right: 20px !important;
        }
        .sortable-header::after {
            content: "\f0dc";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 5px;
            color: #ccc;
        }
        .sortable-header.sort-asc::after {
            content: "\f0de";
            color: #333;
        }
        .sortable-header.sort-desc::after {
            content: "\f0dd";
            color: #333;
        }
    </style>
</head>
<body>
    <div id="app">
        @include('layouts.nav')

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    @yield('assets-js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const getCellValue = (tr, idx) => {
                const cell = tr.children[idx];
                return cell.dataset.sortValue || cell.innerText || cell.textContent;
            };

            const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
                v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
            )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

            document.querySelectorAll('th.sortable-header').forEach(th => th.addEventListener('click', (() => {
                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                const index = Array.from(th.parentNode.children).indexOf(th);
                const isAsc = th.classList.contains('sort-asc');

                // Clear other headers in the same table
                th.parentNode.querySelectorAll('th').forEach(header => {
                    header.classList.remove('sort-asc', 'sort-desc');
                });

                if (isAsc) {
                    th.classList.add('sort-desc');
                } else {
                    th.classList.add('sort-asc');
                }

                Array.from(tbody.querySelectorAll('tr'))
                    .sort(comparer(index, !isAsc))
                    .forEach(tr => tbody.appendChild(tr));
            })));
        });
    </script>
</body>
</html>
