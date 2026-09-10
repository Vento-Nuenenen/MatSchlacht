<form class="d-inline" method="POST" action="{{ route($route, $id) }}" onsubmit="return confirm('Möchten Sie diesen Eintrag wirklich löschen?')">
    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
</form>
