# The User Experience

## Dashboard

URI: /dashboard

The purpose of the dashboard is the creation of new projects. When a user has an idea for an app, they describe it here. Once they've done that a record is created for the idea and they are taken to a page dedicated to that idea where they can build on it.

The dashboard should be set up to allow the user to build out a new project. At the top, there should be a a textarea where the user can describe their project. Below that, there should be a button to save the idea and generate name ideas. When this button is clicked, the project should be given an editable name and saved to the database. The user should then be redirected to the project page for that idea.


## Project Page

URI: /project/{project-uuid}

The project page should have the textarea containing the idea so that they can iterate on it. It should also let them update the name of the project by hand.

When name ideas are generated, they should appear in a Flux table (not an actual HTML table) below the idea description. The textarea should remain in place and visible so that the user can update it at any time. 

Each row of the table should contain the name idea as a header along with one or more available domains that are appropriate for that idea. Each row should also have a button to generate logo ideas. If logo ideas are generated, they should appear within the row as well. Ultimately, these rows will be full-width cards that build out progressively as more information is generated.

Each row should have a button to hide it. This lets people move "rejected" ideas out of their way. But there should be a filter at the top to show any "active" ideas and when unchecked, it should show any previously hidden ideas.

Each row should also have a button to select the name, which will set the name of the project, which will be reflected on the sidebar. Once a name is selected, the UI should be updated to reflect that.

### Project page with selected name

Once the user has selected a name, the UI should be updated to hide all other name ideas. They should be able to continue searching for relevant domains and generating logo ideas. There should also be a button that allows them to "unslect" the name, which does not change it in the database so it's still used in the sidebar and header, but the UI should revert back so that the user can generate new name ideas and revisit old ones.

## Sidebar

The sidebar should have a button at the top to create a new project. When clicked, the user should be taken to the dashboard.

The sidebar should also have all previously created projects, in cronological order with the most recent at the top. When any of these projects is clicked, the user should be taken to that project's edit page.
