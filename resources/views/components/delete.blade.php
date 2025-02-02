<form method="POST" action="{{ route($route, $id) }}">
    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
</form>
