<div class="tree-node">
    <div class="tree-node-content level-{{ $level }}">
        <div class="tree-node-flex">
            @if($account->children && $account->children->count() > 0)
                <span class="tree-toggle">▶</span>
            @else
                <span class="tree-toggle" style="visibility: hidden;">▶</span>
            @endif
            
            <span class="account-code">{{ $account->code }}</span>
            <span class="account-name">{{ $account->name }}</span>
            
            <span class="account-balance {{ $account->current_balance < 0 ? 'negative' : 'positive' }}">
                {{ number_format($account->current_balance, 2) }} BDT
            </span>
        </div>
    </div>
    
    @if($account->children && $account->children->count() > 0)
        <div class="tree-children">
            @foreach($account->children as $child)
                @php
                    $child->total_balance = $child->current_balance;
                    if ($child->children && $child->children->count() > 0) {
                        foreach ($child->children as $grandchild) {
                            $child->total_balance += $grandchild->current_balance;
                        }
                    }
                @endphp
                @include('accounts.partials.tree-node', ['account' => $child, 'level' => $level + 1])
            @endforeach
            
            @if($level === 0)
                <!-- Show total for primary accounts -->
                <div class="tree-node-content account-total" style="margin-left: {{ ($level + 1) * 30 }}px;">
                    <div class="tree-node-flex">
                        <span class="account-name"><strong>Total {{ $account->name }}</strong></span>
                        <span class="account-balance {{ $account->total_balance < 0 ? 'negative' : 'positive' }}">
                            <strong>{{ number_format($account->total_balance ?? $account->current_balance, 2) }} BDT</strong>
                        </span>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
