Story marked Ready
   ↓
Piper creates Sprint
   - Sprint has ONE goal
   - Stories selected
   - Sprint context frozen
   ↓
Mason
   - Reads only Sprint context
   - Generates SOLID Spec
   - Decomposes Spec → Atomic Tasks
   ↓
Tasks enter task_queue (minimal payload)
   ↓
Goose executes one task
   ↓
Run saved (artifacts + log)
   ↓
QAQueue evaluates
   ↓
If retry → requeue with guidance
If success → mark task done
If low confidence → escalate
