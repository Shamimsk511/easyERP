<div class="tree-node">
    <div class="tree-node-content level-{{ $level }}">
        <div class="tree-node-flex">
            @if($account->children->count() > 0)
                <span class="tree-toggle"><i class="fas fa-chevron-right"></i></span>
            @else
                <span class="tree-toggle"></span>
            @endif
            
            <span class="account-code">{{ $account->code }}</span>
            <a href="{{ route('accounts.journal', $account) }}" class="account-name text-decoration-none" style="color: inherit;">
                {{ $account->name }}
            </a>
            
            <span class="account-balance {{ $account->current_balance >= 0 ? 'positive' : 'negative' }}">
                {{ number_format(abs($account->current_balance), 2) }}
            </span>
            
            <div class="ml-2">
                <a href="{{ route('accounts.journal', $account) }}" class="btn btn-sm btn-primary" title="View Journal">
                    <i class="fas fa-book"></i>
                </a>
            </div>
        </div>
    </div>
    
    @if($account->children->count() > 0)
        <div class="tree-children">
            @foreach($account->children as $child)
                @include('accounts.partials.tree-node', ['account' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
