// Format renvoyé par MassAffectationController::analyze()
export interface MassAffectationAnalysis {
  signalementsToAffect: number[]
  signalementsAlreadyAffected: number[]
  signalementsOutOfPerimeter: number[]
  partnerInterconnection: boolean
}
