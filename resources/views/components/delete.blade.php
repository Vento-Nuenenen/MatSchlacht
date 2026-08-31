<form class="d-inline" method="POST" action="{{ route($route, $id) }}">
    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-danger">Löschen</button>
</form>
