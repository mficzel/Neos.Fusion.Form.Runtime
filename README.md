# Fusion Form Runtime

Pure fusion form runtime with afx support!

## !!! This is experimental and may turn out to be a totally stupid idea!!!


## Define a form with validation and finishing actions entirely in fusion:

```
prototype(Form.Test:Content.ExampleForm) < prototype(Neos.Neos:ContentComponent) {

    renderer = Neos.Fusion.Form.Runtime:SinglePageForm {
        identifier = "exampleForm"

        form = afx`
            <fieldset>
                <legend>name</legend>
                <div>
                    <label>first name</label><Neos.Fusion.Form:Input field.name="firstName" />
                </div>
                <div>
                    <label>last name</label><Neos.Fusion.Form:Input field.name="lastName" />
                </div>
            </fieldset>
            <div>
                <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
            </div>
        `

        validator {
            firstName =  Neos.Fusion.Form.Runtime:Validator.NotEmpty
            lastName =  Neos.Fusion.Form.Runtime:Validator.NotEmpty
        }

        finisher = afx`<h1>Thank you {form.data.firstName} {form.data.lastName}</h1>`
    }
}
``` 

## Planned features

- multi page forms
- validation
