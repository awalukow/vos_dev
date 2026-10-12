@if(auth('portal')->user()->isAdministrator())
<input type="hidden" name="parameters_locked" value="0">
<label class="check"><input type="checkbox" name="parameters_locked" value="1" @checked($method->parameters_locked)> Lock parameters for non-admin roles</label>
<p class="tiny muted">Applies only to this payment method. Administrators can still edit it.</p>
@elseif($method->parameters_locked)
<p class="notice" role="status">Locked by an administrator. You can view this method, but cannot change its parameters.</p>
@endif
