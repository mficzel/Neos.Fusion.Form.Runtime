# Fusion Form Runtime

Pure fusion form runtime with afx support!

## !!! This is experimental and may turn out to be a totally stupid idea!!!

## Pain points i try to adress with this

- Ist is hard to get access to node-data in finishers (email address, redirect targets)
- It is hard to configure parts of the form from node-data (disable steps, disable parts of the form)
- It is tedious to render custom markup for (grids, fieldTypes, content between fields)
- Forms are very hard to extend as you had to deal with settings.yaml, form.yaml and Fluid  

## Possible new pain points

- Validation is defined seperately from rendering
- Every property needs at least one validator

## Define a form with validation and finishing actions entirely in fusion:

```
prototype(Form.Test:Content.ExampleForm) < prototype(Neos.Neos:ContentComponent) {

    renderer = Neos.Fusion.Form:MultiStepForm {

        data = Neos.Fusion:DataStructure {
            firstName = "aaaa"
        }

        identifier = "exampleForm2"

        steps {

            first {
                renderer = afx`
                    <fieldset>
                        <legend>name</legend>
                        <Neos.Fusion.Form:Neos.BackendModule.FieldContainer field.name="firstName" label="First Name">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:Neos.BackendModule.FieldContainer>
                        <Neos.Fusion.Form:Neos.BackendModule.FieldContainer field.name="lastName" label="Last Name">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:Neos.BackendModule.FieldContainer>
                    </fieldset>
                    <div>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `

                validators {
                    firstName.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                    lastName.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                }
            }

            second {
                renderer = afx`
                    <fieldset>
                        <legend>address</legend>
                        <Neos.Fusion.Form:Neos.BackendModule.FieldContainer field.name="street" label="Street">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:Neos.BackendModule.FieldContainer>
                        <Neos.Fusion.Form:Neos.BackendModule.FieldContainer field.name="city" label="City">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:Neos.BackendModule.FieldContainer>
                    </fieldset>
                    <div>
                        <Neos.Fusion.Form:Button field.name="__step" field.value="first">Back</Neos.Fusion.Form:Button>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `

                validators {
                    street.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                    city.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                }
            }

            confirmation {
                renderer = afx`
                    <h1>Confirm to submit {data.firstName} {data.lastName} from {data.city}, {data.street}</h1>
                    <div>
                        <Neos.Fusion.Form:Button field.name="__step" field.value="second">Back</Neos.Fusion.Form:Button>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `
            }
        }

        actions {
            message {
                identifier = 'Neos.Fusion.Form.Runtime:Message'
                options.content = afx`<h1>Thank you {data.firstName} {data.lastName} from {data.city}, {data.street}</h1>`
            }

            email {
                identifier = 'Neos.Fusion.Form.Runtime:Email'
                options {
                    from = ${q(node).property('mailFrom')}
                    to = ${q(node).property('mailTo')}
                    subject = ${q(node).property('mailSubject')}
                    text = afx`Thank you {data.firstName} {data.lastName} from {data.city}, {data.street}`
                }
            }

            redirect {
                identifier = 'Neos.Fusion.Form.Runtime:Redirect'
                options {
                    uri = Neos.Neos:NodeUri {
                        node = ${q(node).property('thankyou')}
                    }
                }
            }
        }
    }
}
``` 
