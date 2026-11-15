{{-- Buttons for the state transitions the user may perform; expects $order, optional $formClass --}}
@foreach(app(\App\Services\OrderWorkflow::class)->availableTransitions(auth()->user(), $order) as $transition)
    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline {{ $formClass ?? '' }}">
        @csrf
        <input type="hidden" name="to" value="{{ $transition['to'] }}">
        <button type="submit" class="btn {{ $transition['button'] }}">{{ $transition['label'] }}</button>
    </form>
@endforeach
