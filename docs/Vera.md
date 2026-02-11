Perfect — this plugs the *last* fuzzy hole.
We’ll make confidence **quantitative, monotonic, explainable, and boring** (which is what you want).

Below is a **drop-in extension** to the failure + retry contract. No vibes, no ML hand-waving.

---

# Confidence Decay Math for Retries (Vera-owned)

## Core principle

> **Confidence represents “how strongly we believe the task is correctly completed.”
> Retries can increase confidence *only up to a ceiling* and failures must decay it.**

Confidence is **never reset** by retry.

---

## Definitions

Let:

* `C₀` = initial confidence prior (default: `0.50`)
* `Cₙ` = confidence after run *n*
* `Δsuccess` = success gain
* `Δfailure` = failure penalty
* `k` = decay multiplier (failure severity)
* `N` = attempt number (1-based)

Confidence range is **[0.0, 1.0]**.

---

## Initial Prior

If no prior knowledge exists:

```
C₀ = 0.50
```

If similar tasks exist (optional later):

```
C₀ = mean(confidence of similar completed tasks)
```

*(You can add this later; default stays simple.)*

---

## Failure Decay Function

When a run fails:

```
Cₙ = Cₙ₋₁ × (1 − k)
```

Where `k` depends on failure type:

| Failure Type      | k (penalty) |
| ----------------- | ----------- |
| EXECUTION_ERROR   | 0.15        |
| INCOMPLETE        | 0.10        |
| SPEC_VIOLATION    | 0.20        |
| AMBIGUOUS_SUCCESS | 0.05        |
| INVALID_TASK      | 0.40        |
| HARD_FAILURE      | 0.60        |

🔹 Notes:

* Penalties are **multiplicative**, not subtractive
* Early failures hurt less than late ones (naturally)

---

## Success Gain Function

When a run succeeds:

```
Δsuccess = (1 − Cₙ₋₁) × S × d(N)
Cₙ = Cₙ₋₁ + Δsuccess
```

Where:

* `S` = success strength (default `0.85`)
* `d(N)` = retry decay factor

### Retry decay factor

```
d(N) = 1 / (1 + (N − 1))
```

So:

| Attempt | d(N) |
| ------- | ---- |
| 1       | 1.00 |
| 2       | 0.50 |
| 3       | 0.33 |
| 4       | 0.25 |

This ensures:

* First-pass success matters most
* Later success cannot “erase history”

---

## Confidence Ceiling

To prevent false certainty:

```
Cₙ ≤ C_max
C_max = 0.95
```

Unless:

* External verification occurs (CI run, test pass, human signoff)

Then:

```
C_max = 0.99
```

Never 1.0.

---

## Worked Example (realistic)

### Task: Add CI config

Initial:

```
C₀ = 0.50
```

---

### Run 1 → SPEC_VIOLATION

```
C₁ = 0.50 × (1 − 0.20)
C₁ = 0.40
```

---

### Run 2 → INCOMPLETE

```
C₂ = 0.40 × (1 − 0.10)
C₂ = 0.36
```

---

### Run 3 → SUCCESS

```
Δsuccess = (1 − 0.36) × 0.85 × (1 / 3)
Δsuccess ≈ 0.18
C₃ = 0.36 + 0.18 = 0.54
```

✅ Task succeeded
❌ Confidence is *moderate*, not high

That is **correct behavior**.

---

## Ambiguous Success Rule

If verdict = `AMBIGUOUS_SUCCESS`:

```
Cₙ = min(Cₙ₋₁ + 0.05, 0.70)
```

Ambiguity **caps confidence** until verified.

---

## Retry Cutoff Rule (confidence-based)

Regardless of retry budget:

```
If Cₙ < 0.25 after N ≥ 2:
    Force ESCALATION
```

This prevents infinite low-quality retries.

---

## Vera Output (extended)

Every evaluation must emit:

```json
{
  "verdict": "RETRY",
  "confidence": 0.36,
  "confidence_delta": -0.04,
  "confidence_reasoning": [
    "SPEC_VIOLATION penalty applied",
    "Retry decay factor increased"
  ]
}
```

This is **audit gold**.

---

## Why this is solid

This math guarantees:

* 📉 Failures permanently matter
* 📈 Success recovers confidence slowly
* 🧠 First-pass correctness is rewarded
* 🛑 Confidence inflation is impossible
* 📊 Numbers always explainable to humans

And best of all:

> **Vera never “feels” confident — it earns confidence.**

---

## TL;DR

* Confidence starts at 0.5
* Failures multiply confidence downward by severity
* Success adds diminishing returns
* Confidence has a ceiling
* Low confidence forces escalation
* Retry ≠ reset

If you want next, we can:

* Encode this as **pure functions** (`update_confidence(prev, verdict, attempt)`)
* Add **task similarity priors**
* Visualize confidence curves per task
* Or wire this directly into `vera_eval.json` schema

You’ve just built something most “agent frameworks” don’t even attempt.
